<?php

namespace Repositories;

use App\Models\Space;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use PDOException;

class SpaceRepository extends BaseRepository
{
    const MODEL = Space::class;
    const DATE = Carbon::class;

    public static function create(array $data): Space
    {
        return DB::transaction(function () use ($data) {
            return self::insert(self::MODEL, $data);
        });
    }

    public static function all(): Collection
    {
        return self::getAll(self::MODEL);
    }

    public static function search(array $filters): ?array
    {
        return self::getBy(self::MODEL, $filters)->toArray();
    }

    public static function paginate(array $filters, int $perPage = 15)
    {
        try {
            // Eager load relationships to avoid N+1 and get images
            $query = self::MODEL::query()
                ->with(['spaceType', 'status', 'images', 'features']);

            if (isset($filters['capacity'])) {
                $query->where('capacity', '>=', $filters['capacity']);
            }

            if (isset($filters['spaces_type_id'])) {
                $query->where('spaces_type_id', $filters['spaces_type_id']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            // 1. Paginate first (Limiting the number of records processed)
            $results = $query->paginate($perPage);

            // 2. Fetch availability only for the current page items
            $today = self::DATE::now()->format('Y-m-d');
            $startTime = self::DATE::now()->subHours(1)->format('H:i:s');
            $endTime = self::DATE::now()->format('H:i:s');

            // Load reservations for the fetched spaces that overlap with the current time window
            $results->getCollection()->load(['reservations' => function ($q) use ($today, $startTime, $endTime) {
                $q->where('event_date', $today)
                    ->where('start_time', '<', $endTime) // Starts before "now"
                    ->where('end_time', '>', $startTime) // Ends after "1 hour ago"
                    ->whereHas('status', function ($sq) {
                        $sq->whereNotIn('name', ['canceled', 'cancelada']);
                    });
            }]);

            // 3. Transform the collection to set the availability status
            $results->getCollection()->transform(function ($space) {
                // If there are any reservations in the loaded relation, it's occupied
                $isOccupied = $space->reservations->isNotEmpty();
                $space->setAttribute('availability_status', $isOccupied ? 'ocupado' : 'disponible');

                // Remove reservations from the output to keep it clean (optional, keeping it clean is good)
                $space->unsetRelation('reservations');

                return $space;
            });

            return $results;
        } catch (PDOException $e) {
            throw new \Exception(BaseRepository::ERRORS[$e->getCode()] . " {$e->getMessage()}");
        } catch (\Throwable $th) {
            throw new \Exception("Error al obtener el listado de espacios {$th->getMessage()}");
        }
    }

    public static function updateSpace(array $filters, array $data): ?Space
    {
        return  DB::transaction(function () use ($filters, $data) {
            self::update(self::MODEL, $filters, $data);
            $space = self::getOneBy(self::MODEL, $filters);
            if (!$space || !$space instanceof Space) {
                throw new \Exception("Error al actualizar el espacio");
            }
            return $space;
        });
    }

    public static function findByUuid(string $uuid): ?Space
    {
        return self::getOneBy(self::MODEL, ['uuid' => $uuid]);
    }

    public static function getAvailableSpaces(array $filters)
    {
        $date = $filters['fecha_deseada'];

        return self::MODEL::query()
            ->active()
            ->byType($filters['space_type_id'] ?? null)
            ->byMinCapacity($filters['min_capacity'] ?? null)
            ->withAllFeatures($filters['feature_ids'] ?? null)
            ->byPriceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null)
            ->availableOnDate($date)
            ->notFullyBooked($date)
            ->get();
    }
}
