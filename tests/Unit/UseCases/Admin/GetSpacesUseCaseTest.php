<?php

namespace Tests\Unit\UseCases\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Repositories\Contracts\SpaceRepositoryInterface;
use Tests\TestCase;
use UseCases\Admin\GetSpacesUseCase;

class GetSpacesUseCaseTest extends TestCase
{
    public function test_execute_calls_repository_paginate_admin()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);

        // 2. Expectation
        $filters = ['name' => 'Sala'];
        $perPage = 10;

        $paginator = new LengthAwarePaginator([], 0, 10);

        $repository->shouldReceive('paginateAdmin')
            ->once()
            ->with($filters, $perPage)
            ->andReturn($paginator);

        // 3. Execute UseCase
        $useCase = new GetSpacesUseCase($repository);
        $result = $useCase->execute($filters, $perPage);

        // 4. Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
