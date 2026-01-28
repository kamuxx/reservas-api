<?php

namespace UseCases\Admin;

use Repositories\Contracts\SpaceRepositoryInterface;

class GetDashboardStatsUseCase
{
    protected $repository;

    public function __construct(SpaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        return $this->repository->getDashboardStats();
    }
}
