<?php

namespace Repositories;

use App\Models\Space;
use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SpaceRepository extends BaseRepository
{
    const MODEL = Space::class;

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
