<?php

namespace Tests\Unit\UseCases\Admin;

use Mockery;
use Repositories\Contracts\SpaceRepositoryInterface;
use Tests\TestCase;
use UseCases\Admin\GetDashboardStatsUseCase;

class GetDashboardStatsUseCaseTest extends TestCase
{
    public function test_get_dashboard_stats_calls_repository()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);
        $expectedStats = ['kpi' => [], 'charts' => []];

        $repository->shouldReceive('getDashboardStats')
            ->once()
            ->andReturn($expectedStats);

        // 2. Execute
        $useCase = new GetDashboardStatsUseCase($repository);
        $result = $useCase->execute();

        // 3. Assert
        $this->assertEquals($expectedStats, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
