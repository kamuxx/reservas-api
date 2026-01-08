<?php 

namespace Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Repositories\Contracts\RepositoryContract;

class BaseRepository implements RepositoryContract
{
    private static function isModelValid(string $modelClassName): bool
    {
        return is_subclass_of($modelClassName, Model::class);
    }
 
    public static function insert(string $modelClassName, array $data): Model
    {
        if(!self::isModelValid($modelClassName)) throw new \Exception("El modelo debe ser una subclase de Model");
        return $modelClassName::create($data);   
    }

    public static function getAll(string $modelClassName): Collection
    {
        if(!self::isModelValid($modelClassName)) throw new \Exception("El modelo debe ser una subclase de Model");
        return $modelClassName::all();
    }

    public static function getBy(string $modelClassName, array $filters): ?Collection
    {
        if(!self::isModelValid($modelClassName)) throw new \Exception("El modelo debe ser una subclase de Model");
        return $modelClassName::where($filters)->get();
    }

    public static function getOneBy(string $modelClassName, array $filters): ?Model
    {
        if(!self::isModelValid($modelClassName)) throw new \Exception("El modelo debe ser una subclase de Model");
        $record = $modelClassName::where($filters)->first();
        return $record;
    }

    public static function update(string $modelClassName, array $filters, array $data): bool
    {
        if(!self::isModelValid($modelClassName)) throw new \Exception("El modelo debe ser una subclase de Model");
        return $modelClassName::where($filters)->update($data);
    }
}