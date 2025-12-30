<?php 

namespace Repositories;

use App\Models\Space;
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

    public static function all(): array
    {
        return self::getAll(self::MODEL);
    }

    public static function search(array $filters): ?array
    {
        return self::getBy(self::MODEL,$filters);
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

}