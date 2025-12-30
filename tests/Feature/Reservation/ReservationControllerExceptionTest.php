<?php

namespace Tests\Feature\Reservation;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use UseCases\ReservationUseCases;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ReservationControllerExceptionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $reservationUseCases;

    protected function setUp(): void
    {
        parent::setUp();

        $roleUser = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $statusActive = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);

        $this->user = User::factory()->create([
            'role_id' => $roleUser->uuid,
            'status_id' => $statusActive->uuid,
        ]);

        $this->reservationUseCases = Mockery::mock(ReservationUseCases::class);
        $this->app->instance(ReservationUseCases::class, $this->reservationUseCases);
    }

    public function test_store_throws_409(): void
    {
        $space = \App\Models\Space::factory()->create();
        $this->reservationUseCases->shouldReceive('create')->andThrow(new \Exception("Overlap error", 409));

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/reservations', [
                'space_id' => $space->uuid,
                'event_name' => 'Event',
                'event_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'Overlap error');
    }

    public function test_store_throws_404(): void
    {
        $space = \App\Models\Space::factory()->create();
        $this->reservationUseCases->shouldReceive('create')->andThrow(new \Exception("Not found", 404));

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/reservations', [
                'space_id' => $space->uuid,
                'event_name' => 'Event',
                'event_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Not found');
    }

    public function test_store_throws_general_exception(): void
    {
        $space = \App\Models\Space::factory()->create();
        $this->reservationUseCases->shouldReceive('create')->andThrow(new \Exception("General error", 500));

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/reservations', [
                'space_id' => $space->uuid,
                'event_name' => 'Event',
                'event_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al crear la reserva', $response->json('message'));
    }

    public function test_destroy_throws_403(): void
    {
        $this->reservationUseCases->shouldReceive('cancel')->andThrow(new \Exception("Forbidden", 403));

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/reservations/some-uuid');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Forbidden');
    }

    public function test_destroy_throws_404(): void
    {
        $this->reservationUseCases->shouldReceive('cancel')->andThrow(new \Exception("Not found", 404));

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/reservations/some-uuid');

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Not found');
    }

    public function test_destroy_throws_422(): void
    {
        $this->reservationUseCases->shouldReceive('cancel')->andThrow(new \Exception("Unprocessable", 422));

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/reservations/some-uuid');

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Unprocessable');
    }

    public function test_destroy_throws_general_exception(): void
    {
        $this->reservationUseCases->shouldReceive('cancel')->andThrow(new \Exception("General error", 500));

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/reservations/some-uuid');

        $response->assertStatus(500);
        $this->assertStringContainsString('Error al cancelar la reserva', $response->json('message'));
    }

    public function test_destroy_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/reservations/some-uuid');

        // Note: middleware 'auth:api' might catch this first and return 401
        // But let's see if it hits the controller's check
        $this->assertTrue(in_array($response->status(), [401]));
    }
}
