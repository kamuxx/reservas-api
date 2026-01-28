<?php

namespace UseCases\Admin;

use App\Models\Space;
use Repositories\Contracts\SpaceRepositoryInterface;

class CreateSpaceUseCase
{
    protected $repository;

    public function __construct(SpaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $data): Space
    {
        return $this->repository->create($data);
    }
}
