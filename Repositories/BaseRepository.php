<?php 

namespace Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Repositories\Contracts\RepositoryContract;

class BaseRepository implements RepositoryContract
{
    const ERRORS = [
    // --- ERRORES DE CONEXIÓN ---
    '2002'  => "No se puede conectar al servidor (host no encontrado o caído).",
    '1045'  => "Acceso denegado: Usuario o contraseña incorrectos.",
    '1049'  => "La base de datos especificada no existe.",

    // --- ERRORES DE ESTRUCTURA (Tablas y Columnas) ---
    '42S02' => "La tabla solicitada no existe en la base de datos.", // Base table not found
    '42S22' => "Una de las columnas especificadas no existe en la tabla.", // Column not found
    '42000' => "Error de sintaxis SQL o restricción de acceso.",

    // --- ERRORES DE RESTRICCIONES (Constraints) ---
    '1062'  => "Entrada duplicada: Ya existe un registro con este valor único.",
    '1451'  => "No se puede eliminar: El registro está siendo usado en otra tabla (Llave foránea).",
    '1452'  => "No se puede agregar/actualizar: La referencia (ID) no existe en la tabla principal.",
    '1364'  => "Campo obligatorio: Falta un valor para una columna que no acepta nulos.",

    // --- ERRORES DE DATOS ---
    '1265'  => "Los datos se truncaron: El valor es demasiado largo para el tipo de columna.",
    '1292'  => "Formato de fecha o número incorrecto.",
];
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