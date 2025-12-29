<?php 

namespace Repositories\Contracts;
use Illuminate\Database\Eloquent\Model;

interface RepositoryContract
{
    public static function insert(string $modelClassName, array $data): Model;
    public static function getBy(string $modelClassName, array $filters): ?array;
    public static function getOneBy(string $modelClassName, array $filters): ?Model;
    public static function update(string $modelClassName, array $filters, array $data): bool;
}