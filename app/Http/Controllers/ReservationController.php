<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\Reservation\CreateReservationRequest;
use UseCases\ReservationUseCases;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private ReservationUseCases $reservationUseCases) {}

    /**
     * Crea una nueva reserva (HU-009).
     */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            
            $reservation = $this->reservationUseCases->create(
                $request->validated(),
                $user->uuid
            );

            return $this->success(
                201,
                "Reserva creada exitosamente",
                $reservation->toArray()
            );
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code === 409) {
                return $this->error(409, $e->getMessage(), null);
            }
            if ($code === 404) {
                return $this->error(404, $e->getMessage(), null);
            }
            
            return $this->serverError($e, "Error al crear la reserva");
        }
    }

    /**
     * Cancela una reserva (HU-010).
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->error(401, "No autenticado", null);
            }

            // Cargar el rol si no está cargado
            $user->load('role');

            // Asegurarse de tener el nombre del rol
            $roleName = $user->role->name ?? 'user';

            $reservation = $this->reservationUseCases->cancel(
                $id,
                $user->uuid,
                $roleName
            );

            return $this->success(
                200,
                "Reserva cancelada exitosamente",
                $reservation->toArray()
            );
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, [403, 404, 422])) {
                return $this->error($code, $e->getMessage(), null);
            }
            return $this->serverError($e, "Error al cancelar la reserva");
        }
    }
}
