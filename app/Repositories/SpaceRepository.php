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

}