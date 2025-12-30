<?php

namespace Tests\Unit\UseCases;

use Tests\TestCase;
use UseCases\SpaceUseCases;
use Repositories\SpaceRepository;
use Repositories\ReservationRepository;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

class SpaceUseCasesTest extends TestCase
{
    use RefreshDatabase;

    private $spaceRepository;
    private $reservationRepository;
    private $spaceUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->spaceRepository = Mockery::mock(SpaceRepository::class);
        $this->reservationRepository = Mockery::mock(ReservationRepository::class);
        $this->spaceUseCases = new SpaceUseCases($this->spaceRepository, $this->reservationRepository);

        // Ensure roles and statuses exist for factories if needed
        if (!\App\Models\Role::where('name', 'user')->exists()) {
            \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
        if (!\App\Models\Status::where('name', 'active')->exists()) {
            \App\Models\Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_space_success(): void
    {
        $data = [
            'name' => 'Test Space',
            'created_by' => 'user-uuid'
        ];

        $space = new Space($data);
        $space->uuid = 'space-uuid';

        $this->spaceRepository->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($space);

        $result = $this->spaceUseCases->register($data);

        $this->assertInstanceOf(Space::class, $result);
        $this->assertEquals('Test Space', $result->name);
    }

    public function test_register_space_throws_exception_on_failure(): void
    {
        $data = ['name' => 'Fail'];

        $this->spaceRepository->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception("Error al registrar el espacio"));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al registrar el espacio");

        $this->spaceUseCases->register($data);
    }

    public function test_list_spaces(): void
    {
        $filters = ['per_page' => 10];
        
        $this->spaceRepository->shouldReceive('paginate')
            ->once()
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->spaceUseCases->list($filters, false);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_find_space_not_found(): void
    {
        $this->spaceRepository->shouldReceive('findByUuid')
            ->once()
            ->with('uuid')
            ->andReturn(null);

        $result = $this->spaceUseCases->find('uuid');
        $this->assertNull($result);
    }

    public function test_check_availability(): void
    {
        $data = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-02'
        ];

        $this->reservationRepository->shouldReceive('getOccupiedSlots')
            ->once()
            ->with('space-uuid', '2025-01-01', '2025-01-02')
            ->andReturn(new \Illuminate\Database\Eloquent\Collection(['slot1']));

        $result = $this->spaceUseCases->checkAvailability('space-uuid', $data);
        $this->assertEquals(['slot1'], $result);
    }

    public function test_update_space_success(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $space = new Space(['name' => 'Old Name']);
        $space->uuid = 'space-uuid';

        $data = ['name' => 'New Name'];
        $updatedSpace = new Space(['name' => 'New Name']);
        $updatedSpace->uuid = 'space-uuid';

        $this->spaceRepository->shouldReceive('updateSpace')
            ->once()
            ->andReturn($updatedSpace);

        $this->spaceUseCases->update($space, $data);
        
        $this->assertDatabaseHas('entity_audit_trails', [
            'entity_name' => 'spaces',
            'operation' => 'update',
            'entity_id' => 'space-uuid'
        ]);
    }

    public function test_find_space_inactive_as_non_admin(): void
    {
        $space = new Space(['is_active' => false]);
        $space->uuid = 'inactive-uuid';

        $this->spaceRepository->shouldReceive('findByUuid')
            ->once()
            ->with('inactive-uuid')
            ->andReturn($space);

        $result = $this->spaceUseCases->find('inactive-uuid', false);
        $this->assertNull($result);
    }

    public function test_find_space_inactive_as_admin(): void
    {
        $space = new Space(['is_active' => false]);
        $space->uuid = 'inactive-uuid';

        $this->spaceRepository->shouldReceive('findByUuid')
            ->once()
            ->with('inactive-uuid')
            ->andReturn($space);

        $result = $this->spaceUseCases->find('inactive-uuid', true);
        $this->assertEquals($space, $result);
    }

    public function test_get_available_spaces(): void
    {
        $filters = ['fecha_deseada' => '2025-01-01', 'space_type_id' => 'type-uuid'];

        $this->spaceRepository->shouldReceive('getAvailableSpaces')
            ->once()
            ->with('2025-01-01', 'type-uuid')
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([]));

        $result = $this->spaceUseCases->getAvailableSpaces($filters);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_register_space_invalid_return(): void
    {
        $data = ['name' => 'Invalid Return'];

        // Mockery will throw TypeError if we try to return null for a non-nullable return type
        $this->spaceRepository->shouldReceive('create')
            ->once()
            ->andThrow(new \TypeError("Return value must be of type App\Models\Space, null returned"));

        $this->expectException(\TypeError::class);
        $this->spaceUseCases->register($data);
    }

    public function test_update_space_invalid_return(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $space = new Space(['name' => 'Old Name']);
        $space->uuid = 'space-uuid';

        $data = ['name' => 'New Name'];

        $this->spaceRepository->shouldReceive('updateSpace')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error al actualizar el espacio");

        $this->spaceUseCases->update($space, $data);
    }

    public function test_list_spaces_as_admin(): void
    {
        $filters = ['per_page' => 10];
        
        $this->spaceRepository->shouldReceive('paginate')
            ->once()
            ->with($filters, 10)
            ->andReturn(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10));

        $result = $this->spaceUseCases->list($filters, true);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }
}
