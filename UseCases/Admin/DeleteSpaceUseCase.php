<?php

namespace UseCases\Admin;

use App\Models\Space;
use Repositories\Contracts\SpaceRepositoryInterface;

class DeleteSpaceUseCase
{
    protected $repository;

    public function __construct(SpaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(string $uuid): bool
    {
        // Business Rule: Check for active reservations
        if ($this->repository->hasActiveReservations($uuid)) {
            throw new \Exception('No se puede eliminar el espacio porque tiene reservas activas.', 409);
        }

        return $this->repository->delete(Space::class, ['uuid' => $uuid]);
    }
}
