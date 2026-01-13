<?php

namespace UseCases;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\Status;
use App\Models\PricingRule;
use Illuminate\Support\Facades\DB;
use Repositories\ReservationRepository;
use Repositories\SpaceRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReservationUseCases
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private SpaceRepository $spaceRepository
    ) {}

    /**
     * Crea una nueva reserva con validación atómica (HU-009).
     *
     * @param array $data
     * @param string $userUuid
     * @return Reservation
     * @throws \Exception
     */
    public function create(array $data, string $userUuid): Reservation
    {
        return DB::transaction(function () use ($data, $userUuid) {
            // 1. Bloquear el espacio para evitar doble reserva concurrente
            $space = Space::with('pricingRule')->where('uuid', $data['space_id'])->lockForUpdate()->first();

            if (!$space) {
                throw new \Exception("El espacio no existe.", 404);
            }

            // 2. Validar solapamiento (doble check dentro de la transacción)
            if ($this->reservationRepository::hasOverlap($data['space_id'], $data['event_date'], $data['start_time'], $data['end_time'])) {
                throw new \Exception("El espacio ya se encuentra reservado en el horario seleccionado.", 409);
            }

            // 3. Preparar datos
            $status = Status::where('name', 'confirmada')->first();
            if (!$status) {
                $status = Status::where('name', 'agendada')->first();
            }
            if (!$status) {
                $status = Status::where('name', 'active')->first() ?: Status::first();
            }

            // Cálculo básico de precio basado en la regla del espacio
            // Cálculo básico de precio basado en la regla del espacio
            $pricingRule = $space->pricingRule;
            $pricingRuleUuid = $pricingRule ? $pricingRule->uuid : null;

            if (array_key_exists('event_price', $data) && $data['event_price'] !== null) {
                $price = $data['event_price'];
            } else {
                if (!$pricingRule) {
                    // Fallback a una regla por defecto o precio 0
                    $price = 0;
                } else {
                    $price = $this->calculatePrice($pricingRule, $data['start_time'], $data['end_time']);
                }
            }

            $reservationData = [
                'reserved_by' => $userUuid,
                'space_id' => $space->uuid,
                'status_id' => $status->uuid ?? null,
                'event_name' => $data['event_name'],
                'event_description' => $data['event_description'] ?? null,
                'event_date' => $data['event_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'event_price' => $price,
                'pricing_rule_id' => $pricingRuleUuid,
            ];

            // 4. Crear la reserva
            $reservation = $this->reservationRepository::create($reservationData);

            // 5. Registro de auditoría (HU-005 / Requisito transversal)
            DB::table('entity_audit_trails')->insert([
                'entity_name' => 'reservation',
                'entity_id'   => $reservation->uuid,
                'operation'   => 'create',
                'after_state' => json_encode($reservation->toArray()),
                'user_uuid'   => $userUuid,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $reservation;
        });
    }

    /**
     * Cancela una reserva existente (HU-010).
     */
    public function cancel(string $id, string $userUuid, string $roleName): Reservation
    {
        return DB::transaction(function () use ($id, $userUuid, $roleName) {
            $reservation = Reservation::where('uuid', $id)->lockForUpdate()->first();

            if (!$reservation) {
                throw new \Exception("La reserva no existe.", 404);
            }

            // 1. Validar propiedad si es usuario (HU-010 Regla 1)
            if ($roleName !== 'admin' && $reservation->reserved_by !== $userUuid) {
                throw new \Exception("No tiene permisos para cancelar esta reserva.", 403);
            }

            // 2. Validar estado (HU-010 Regla 4: Solo confirmada o agendada para ser flexible)
            $confirmadaStatus = Status::where('name', 'confirmada')->first();
            $agendadaStatus = Status::where('name', 'agendada')->first();

            $validStatusUuids = [];
            if ($confirmadaStatus) $validStatusUuids[] = $confirmadaStatus->uuid;
            if ($agendadaStatus) $validStatusUuids[] = $agendadaStatus->uuid;

            if (!empty($validStatusUuids) && !in_array($reservation->status_id, $validStatusUuids)) {
                throw new \Exception("Solo se pueden cancelar reservas en estado confirmada.", 422);
            }

            // 3. Cambiar estado a cancelada
            $canceladaStatus = Status::where('name', 'cancelada')->first();
            if (!$canceladaStatus) {
                $canceladaStatus = Status::where('name', 'canceled')->first();
            }

            if (!$canceladaStatus) {
                throw new \Exception("Estado 'cancelada' no configurado en el sistema.", 500);
            }

            $reservation->status_id = $canceladaStatus->uuid;
            $reservation->cancellation_by = $userUuid;
            $reservation->save();

            // 4. Auditoría
            DB::table('entity_audit_trails')->insert([
                'entity_name' => 'reservation',
                'entity_id'   => $reservation->uuid,
                'operation'   => 'update',
                'after_state' => json_encode($reservation->toArray()),
                'user_uuid'   => $userUuid,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $reservation;
        });
    }

    /**
     * Calcula el precio de la reserva basado en la regla de precios.
     */
    private function calculatePrice($pricingRule, $start, $end): float
    {
        if (!$pricingRule) return 0.00;

        try {
            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime = Carbon::createFromFormat('H:i', $end);

            // Si la hora de fin es menor a la de inicio, asume día siguiente (aunque la validación lo impide)
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }

            $hours = max(0, $startTime->diffInMinutes($endTime) / 60);

            if ($pricingRule->adjustment_type === 'fixed') {
                return (float) ($pricingRule->price_adjustment * $hours);
            }

            if ($pricingRule->adjustment_type === 'percentage') {
                // Asumiendo un precio base por hora de alguna configuración o del espacio si existiera column
                // Como no hay columna base_price en spaces, usamos el adjustment como tarifa por hora directamente si es positive
                // O si es un recargo sobre un base 0?
                // Revisando la logica actual, parece que solo hay 'price_adjustment'.
                // Si es porcentaje, necesitamos una base. Como no hay base visible aqui,
                // asumiremos que para este MVP el 'fixed' es el precio por hora.
                if ($pricingRule->price_adjustment > 0) {
                    return (float) ($pricingRule->price_adjustment * $hours);
                }
            }

            return 0.00;
        } catch (\Throwable $th) {
            // Log error if needed
            return 0.00;
        }
    }

    /**
     * Obtiene el detalle de una reserva validando permisos.
     */
    public function getDetail(string $reservationUuid, string $userUuid, string $roleName)
    {
        $reservation = $this->reservationRepository->getDetail($reservationUuid);

        if (!$reservation) {
            throw new \Exception("La reserva no existe.", 404);
        }

        // Valida si el usuario es dueño de la reserva o es admin
        if ($roleName !== 'admin' && $reservation->user_uuid !== $userUuid) {
            throw new \Exception("No tiene permisos para ver esta reserva.", 403);
        }

        return $reservation;
    }

    /**
     * Obtiene las reservas de un usuario (HU-Listado).
     */
    public function getByUser(string $userUuid): Collection
    {
        return $this->reservationRepository->getByUser($userUuid);
    }
}
