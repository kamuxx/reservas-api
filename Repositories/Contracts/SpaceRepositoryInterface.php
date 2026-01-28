<?php

namespace Repositories\Contracts;

use App\Models\Space;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SpaceRepositoryInterface extends RepositoryContract
{
    public static function paginateAdmin(array $filters, int $perPage = 15): LengthAwarePaginator;
    public static function getDashboardStats(): array;
    public static function hasActiveReservations(string $uuid): bool;

    // Existing methods from SpaceRepository that should be in interface
    public static function findByUuid(string $uuid): ?Space;
    public static function findByIdWithRelations(string $id): ?Space;
    public static function getAvailableSpaces(array $filters);
    public static function paginate(array $filters, int $perPage = 15);
    public static function updateSpace(array $filters, array $data): ?Space;
}
