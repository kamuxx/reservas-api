<?php

namespace UseCases\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Repositories\Contracts\SpaceRepositoryInterface;

class GetSpacesUseCase
{
    protected $repository;

    public function __construct(SpaceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateAdmin($filters, $perPage);
    }
}
