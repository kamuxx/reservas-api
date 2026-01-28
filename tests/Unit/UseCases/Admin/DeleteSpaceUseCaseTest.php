<?php

namespace Tests\Unit\UseCases\Admin;

use App\Models\Space;
use Mockery;
use Repositories\Contracts\SpaceRepositoryInterface;
use Tests\TestCase;
use UseCases\Admin\DeleteSpaceUseCase;

class DeleteSpaceUseCaseTest extends TestCase
{
    public function test_delete_space_throws_conflict_if_active_reservations_exist()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);
        $uuid = 'space-uuid';

        // 2. Expectation: Check for active reservations
        $repository->shouldReceive('hasActiveReservations')
            ->once()
            ->with($uuid)
            ->andReturn(true); // Has active reservations

        // 3. Execution & Assertion
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('No se puede eliminar el espacio porque tiene reservas activas.');

        $useCase = new DeleteSpaceUseCase($repository);
        $useCase->execute($uuid);
    }

    public function test_delete_space_calls_repository_delete()
    {
        // 1. Mock Repository
        $repository = Mockery::mock(SpaceRepositoryInterface::class);
        $uuid = 'space-uuid';

        // 2. Expectation: Check active reservations (False) then Delete (True)
        $repository->shouldReceive('hasActiveReservations')
            ->once()
            ->with($uuid)
            ->andReturn(false);

        // Delete signature: delete($modelClassName, $filters)
        // SpaceRepository::delete uses Model param? 
        // BaseRepository::delete takesClassName and filters.
        // SpaceRepositoryInterface::delete signature from RepositoryContract is delete($modelClassName, $filters).
        // UseCase should call it with Space::class.

        $repository->shouldReceive('delete')
            ->once()
            ->with(Space::class, ['uuid' => $uuid])
            ->andReturn(true);

        // 3. Execution
        $useCase = new DeleteSpaceUseCase($repository);
        $result = $useCase->execute($uuid);

        // 4. Assert
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
