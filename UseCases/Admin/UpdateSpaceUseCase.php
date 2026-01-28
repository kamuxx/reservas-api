<?php

namespace UseCases\Admin;

use App\Models\Space;
use Repositories\Contracts\SpaceRepositoryInterface;

class UpdateSpaceUseCase
{
    protected $repository;

    public function __construct(SpaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(string $uuid, array $data): ?Space
    {
        // The repository expects filters and data.
        return $this->repository->updateSpace(['uuid' => $uuid], $data);
    }
}
