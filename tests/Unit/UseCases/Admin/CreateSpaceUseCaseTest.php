<?php

namespace Tests\Unit\UseCases\Admin;

use App\Models\Space;
use Mockery;
use Repositories\Contracts\SpaceRepositoryInterface;
use Tests\TestCase;
use UseCases\Admin\CreateSpaceUseCase;

class CreateSpaceUseCaseTest extends TestCase
{
    public function test_create_space_calls_repository_create()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);

        // 2. Data
        $data = [
            'name' => 'New Space',
            'capacity' => 10,
            'spaces_type_id' => 'some-uuid',
            'location_id' => 'loc-uuid',
            'pricing_rule_id' => 'rule-uuid',
            'status_id' => 'status-uuid',
            'created_by' => 'user-uuid'
        ];
        
        // 3. Expectation
        // We expect Repository::create to receive the data and return a Space model
        $space = new Space($data); // Simulated return
        $repository->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($space);

        // 4. Execute
        $useCase = new CreateSpaceUseCase($repository);
        $result = $useCase->execute($data);

        // 5. Assert
        $this->assertInstanceOf(Space::class, $result);
        $this->assertEquals('New Space', $result->name);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
