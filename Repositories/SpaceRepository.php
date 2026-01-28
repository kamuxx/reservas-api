<?php

namespace Repositories;

use App\Models\Space;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use PDOException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Repositories\Contracts\SpaceRepositoryInterface;

class SpaceRepository extends BaseRepository implements SpaceRepositoryInterface
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
            $query = self::MODEL::query();
                $query->with(['spaceType', 'status', 'images', 'features']);

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

    public static function findByIdWithRelations(string $id): ?Space
    {
        $query = self::MODEL::query()
            ->where('uuid', $id)
            ->with([
                'spaceType',
                'status',
                'images',
                'features',
                'reservations',
                'comments' => function ($query) {
                    $query->byLatest()
                        ->select(['id', 'space_id', 'user_id', 'comment', 'rating', 'created_at'])
                        ->with(['user:id,uuid,name,email,avatar']); // Optimized select for user. Assuming avatar column exists or will be added.
                }
            ]);

        return $query->first();
    }

    public static function paginateAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = self::MODEL::query()
            ->with(['spaceType', 'location', 'pricingRule', 'status', 'created_by' => function ($q) {
                $q->select('uuid', 'name', 'email'); // Optimize user selection
            }]);

        if (isset($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        if (isset($filters['spaces_type_id'])) {
            $query->where('spaces_type_id', $filters['spaces_type_id']);
        }

        return $query->paginate($perPage);
    }

    public static function hasActiveReservations(string $uuid): bool
    {
        return DB::table('reservations')
            ->join('reservation_statuses', 'reservations.status_id', '=', 'reservation_statuses.uuid')
            ->where('reservations.space_id', $uuid)
            ->whereIn('reservation_statuses.status_name', ['confirmada', 'agendada'])
            ->whereNull('reservations.deleted_at')
            ->exists();
    }

    public static function getDashboardStats(): array
    {
        // 1. KPI Stats
        $totalSpaces = self::MODEL::count();
        $totalReservationsMonth = DB::table('reservations')
            ->whereMonth('event_date', now()->month)
            ->whereYear('event_date', now()->year)
            ->whereNull('deleted_at')
            ->count();

        // Estimated Revenue (Simple sum of event_price for confirmed reservations this month)
        $estimatedRevenue = DB::table('reservations')
            ->join('reservation_statuses', 'reservations.status_id', '=', 'reservation_statuses.uuid')
            ->where('reservation_statuses.status_name', 'confirmada')
            ->whereMonth('reservations.event_date', now()->month)
            ->whereYear('reservations.event_date', now()->year)
            ->whereNull('reservations.deleted_at')
            ->sum('event_price');

        // 2. Occupancy Chart (Top 5 Spaces by Reservations count in current month)
        $occupancyChart = DB::table('reservations')
            ->select('spaces.name', DB::raw('count(*) as total'))
            ->join('spaces', 'reservations.space_id', '=', 'spaces.uuid')
            ->whereMonth('reservations.event_date', now()->month)
            ->whereYear('reservations.event_date', now()->year)
            ->whereNull('reservations.deleted_at')
            ->groupBy('spaces.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 3. Status Chart (Reservations by Status)
        $statusChart = DB::table('reservations')
            ->select('reservation_statuses.status_name', DB::raw('count(*) as total'))
            ->join('reservation_statuses', 'reservations.status_id', '=', 'reservation_statuses.uuid')
            ->whereNull('reservations.deleted_at')
            ->groupBy('reservation_statuses.status_name')
            ->get();

        return [
            'kpi' => [
                'total_spaces' => $totalSpaces,
                'total_reservations_month' => $totalReservationsMonth,
                'estimated_revenue' => $estimatedRevenue
            ],
            'occupancy_chart' => $occupancyChart,
            'status_chart' => $statusChart
        ];
    }

    public static function getAvailableSpaces(array $filters)
    {
        $query = self::MODEL::query();
        $query->with(['spaceType', 'status', 'images', 'features']);

        if (isset($filters['capacity'])) {
            $query->where('capacity', '>=', $filters['capacity']);
        }

        if (isset($filters['spaces_type_id'])) {
            $query->where('spaces_type_id', $filters['spaces_type_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }
}
