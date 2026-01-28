<?php

namespace Tests\Unit\UseCases\Admin;

use App\Models\Space;
use Mockery;
use Repositories\Contracts\SpaceRepositoryInterface;
use Tests\TestCase;
use UseCases\Admin\UpdateSpaceUseCase;

class UpdateSpaceUseCaseTest extends TestCase
{
    public function test_update_space_calls_repository_update()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);

        // 2. Data
        $uuid = 'some-uuid';
        $data = [
            'name' => 'Updated Space',
        ];

        // 3. Expectation
        $space = new Space(array_merge(['uuid' => $uuid], $data));

        // Filters usually used in updateSpace (e.g. ['uuid' => ...])
        $filters = ['uuid' => $uuid];

        $repository->shouldReceive('updateSpace')
            ->once()
            ->with($filters, $data)
            ->andReturn($space);

        // 4. Execute
        $useCase = new UpdateSpaceUseCase($repository);
        $result = $useCase->execute($uuid, $data);

        // 5. Assert
        $this->assertInstanceOf(Space::class, $result);
        $this->assertEquals('Updated Space', $result->name);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
