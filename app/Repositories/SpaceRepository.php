<?php 

namespace Repositories;

use App\Models\Space;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SpaceRepository extends BaseRepository
{
    const MODEL = Space::class;

    public static function create(array $data): Space
    {
        return DB::transaction(function () use ($data) {
            return self::insert(self::MODEL,$data);
        });
    }

    public static function all(): Collection
    {
        return self::getAll(self::MODEL);
    }

    public static function search(array $filters): ?array
    {
        return self::getBy(self::MODEL,$filters)->toArray();
    }

    public static function paginate(array $filters, int $perPage = 15)
    {
        $query = self::MODEL::query();

        if (isset($filters['capacity'])) {
            $query->where('capacity', '>=', $filters['capacity']);
        }

        if (isset($filters['spaces_type_id'])) {
            $query->where('spaces_type_id', $filters['spaces_type_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public static function updateSpace(array $filters, array $data): ?Space
    {
        return  DB::transaction(function () use ($filters, $data) {
            self::update(self::MODEL,$filters,$data);
            $space = self::getOneBy(self::MODEL,$filters);
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

    public static function getAvailableSpaces(string $date, ?string $spaceTypeId = null)
    {
        $query = self::MODEL::query()
            ->where('is_active', true);

        if ($spaceTypeId) {
            $query->where('spaces_type_id', $spaceTypeId);
        }

        // Obtener el UUID del estado 'cancelada' para excluir esas reservas
        $cancelledStatusUuid = DB::table('status')->where('name', 'cancelada')->value('uuid');

        $driver = DB::connection()->getDriverName();
        $timeDiffSql = $driver === 'sqlite' 
            ? 'SUM(strftime("%s", end_time) - strftime("%s", start_time))'
            : 'SUM(TIMESTAMPDIFF(SECOND, start_time, end_time))';

        $query->whereNotExists(function ($subquery) use ($date, $cancelledStatusUuid, $timeDiffSql) {
            $subquery->select(DB::raw(1))
                ->from('reservation')
                ->whereColumn('reservation.space_id', 'spaces.uuid')
                ->whereDate('reservation.event_date', $date)
                ->where('reservation.status_id', '!=', $cancelledStatusUuid)
                ->whereNull('reservation.deleted_at')
                ->groupBy('reservation.space_id')
                ->havingRaw("$timeDiffSql >= 86400");
        });

        // NOTA: strftime es específico de SQLite. Para MySQL sería TIMESTAMPDIFF o similar.
        // Como estamos en un entorno que parece usar SQLite para tests y posiblemente MySQL para dev, 
        // usaremos una aproximación más segura o detectaremos el driver.
        
        return $query->get();
    }
}