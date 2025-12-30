<?php

namespace Tests\Feature\Space;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use UseCases\SpaceUseCases;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class SpaceControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $spaceUseCases;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::where('name', 'admin')->first() ?: Role::create(['name' => 'admin', 'uuid' => (string) Str::uuid()]);
        $statusActive = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->uuid,
            'status_id' => $statusActive->uuid,
        ]);

        $this->spaceUseCases = Mockery::mock(SpaceUseCases::class);
        $this->app->instance(SpaceUseCases::class, $this->spaceUseCases);
    }

    public function test_index_throws_exception(): void
    {
        $this->spaceUseCases->shouldReceive('list')->andThrow(new \Exception("Test Exception"));

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/spaces');

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al obtener el listado de espacios', $response->json('message'));
    }

    public function test_available_throws_exception(): void
    {
        $this->spaceUseCases->shouldReceive('getAvailableSpaces')->andThrow(new \Exception("Test Exception"));

        $response = $this->getJson('/api/spaces/available?fecha_deseada=2025-01-01');

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al obtener los espacios disponibles', $response->json('message'));
    }

    public function test_store_throws_exception(): void
    {
        $spaceType = \App\Models\SpaceType::create(['name' => 'Type 1', 'uuid' => (string) Str::uuid()]);
        $status = Status::create(['name' => 'active_test', 'uuid' => (string) Str::uuid()]);
        $pricingRule = \App\Models\PricingRule::create(['name' => 'Rule 1', 'uuid' => (string) Str::uuid(), 'adjustment_type' => 'fixed', 'price_adjustment' => 10]);

        $this->spaceUseCases->shouldReceive('register')->andThrow(new \Exception("Test Exception"));

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/spaces', [
                'name' => 'Space New',
                'description' => 'Description',
                'capacity' => 10,
                'spaces_type_id' => $spaceType->uuid,
                'status_id' => $status->uuid,
                'pricing_rule_id' => $pricingRule->uuid,
                'is_active' => true,
            ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al crear el espacio', $response->json('message'));
    }

    public function test_update_throws_exception(): void
    {
        $spaceType = \App\Models\SpaceType::create(['name' => 'Type 2', 'uuid' => (string) Str::uuid()]);
        $status = Status::create(['name' => 'active_test_2', 'uuid' => (string) Str::uuid()]);
        $pricingRule = \App\Models\PricingRule::create(['name' => 'Rule 2', 'uuid' => (string) Str::uuid(), 'adjustment_type' => 'fixed', 'price_adjustment' => 10]);
        $space = \App\Models\Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space Update',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $this->admin->uuid,
        ]);

        $this->spaceUseCases->shouldReceive('update')->andThrow(new \Exception("Test Exception"));

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/spaces/{$space->uuid}", [
                'name' => 'Updated Space',
                'description' => 'Updated Description',
                'capacity' => 20,
                'spaces_type_id' => $spaceType->uuid,
                'status_id' => $status->uuid,
                'pricing_rule_id' => $pricingRule->uuid,
                'is_active' => true,
            ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al actualizar el espacio', $response->json('message'));
    }

    public function test_show_throws_exception(): void
    {
        $this->spaceUseCases->shouldReceive('find')->andThrow(new \Exception("Test Exception"));

        $response = $this->getJson("/api/spaces/some-uuid");

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al obtener el detalle del espacio', $response->json('message'));
    }

    public function test_availability_throws_exception(): void
    {
        $this->spaceUseCases->shouldReceive('find')->andReturn(new \App\Models\Space());
        $this->spaceUseCases->shouldReceive('checkAvailability')->andThrow(new \Exception("Test Exception"));

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/spaces/some-uuid/availability?start_date=2025-01-01&end_date=2025-01-02");

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al obtener la disponibilidad del espacio', $response->json('message'));
    }
}
