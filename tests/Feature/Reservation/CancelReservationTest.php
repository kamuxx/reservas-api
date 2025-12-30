<?php

namespace Tests\Feature\Reservation;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\PricingRule;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class CancelReservationTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $admin;
    private $otherUser;
    private $reservation;
    private $tokenUser;
    private $tokenAdmin;
    private $tokenOtherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $roleUser = Role::firstOrCreate(['name' => 'user'], ['uuid' => (string) Str::uuid()]);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin'], ['uuid' => (string) Str::uuid()]);
        
        // Crear estados
        $statusActive = Status::firstOrCreate(['name' => 'active'], ['uuid' => (string) Str::uuid()]);
        $statusConfirmada = Status::firstOrCreate(['name' => 'confirmada'], ['uuid' => (string) Str::uuid()]);
        $statusCancelada = Status::firstOrCreate(['name' => 'cancelada'], ['uuid' => (string) Str::uuid()]);

        // Crear usuarios
        $this->user = User::factory()->create([
            'role_id' => $roleUser->uuid,
            'status_id' => $statusActive->uuid,
        ])->refresh();

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->uuid,
            'status_id' => $statusActive->uuid,
        ])->refresh();

        $this->otherUser = User::factory()->create([
            'role_id' => $roleUser->uuid,
            'status_id' => $statusActive->uuid,
        ])->refresh();

        // Tokens usando JWTAuth::fromUser para no ensuciar el estado del guard
        $this->tokenUser = JWTAuth::fromUser($this->user);
        $this->tokenAdmin = JWTAuth::fromUser($this->admin);
        $this->tokenOtherUser = JWTAuth::fromUser($this->otherUser);

        // Crear espacio
        $spaceType = SpaceType::firstOrCreate(['name' => 'Sala'], ['uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::firstOrCreate(['name' => 'Estándar'], [
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 0,
            'adjustment_type' => 'fixed',
        ]);
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Prueba',
            'description' => 'Descripción',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $statusActive->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $this->admin->uuid,
        ]);

        // Crear reserva
        $this->reservation = Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->user->uuid,
            'space_id' => $space->uuid,
            'status_id' => $statusConfirmada->uuid,
            'event_name' => 'Evento a cancelar',
            'event_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100.00,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);
    }

    public function test_owner_can_cancel_their_reservation(): void
    {
        $response = $this->withToken($this->tokenUser)
            ->deleteJson("/api/reservations/{$this->reservation->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Reserva cancelada exitosamente'
            ]);

        $this->assertDatabaseHas('reservation', [
            'uuid' => $this->reservation->uuid,
            'status_id' => Status::where('name', 'cancelada')->first()->uuid,
            'cancellation_by' => $this->user->uuid
        ]);
    }

    public function test_admin_can_cancel_any_reservation(): void
    {
        $response = $this->withToken($this->tokenAdmin)
            ->deleteJson("/api/reservations/{$this->reservation->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('reservation', [
            'uuid' => $this->reservation->uuid,
            'status_id' => Status::where('name', 'cancelada')->first()->uuid,
            'cancellation_by' => $this->admin->uuid
        ]);
    }

    public function test_user_cannot_cancel_others_reservation(): void
    {
        $response = $this->withToken($this->tokenOtherUser)
            ->deleteJson("/api/reservations/{$this->reservation->uuid}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'No tiene permisos para cancelar esta reserva.'
            ]);
    }

    public function test_cannot_cancel_already_cancelled_reservation(): void
    {
        // Cancelar primero
        $this->reservation->status_id = Status::where('name', 'cancelada')->first()->uuid;
        $this->reservation->save();

        $response = $this->withToken($this->tokenUser)
            ->deleteJson("/api/reservations/{$this->reservation->uuid}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Solo se pueden cancelar reservas en estado confirmada.'
            ]);
    }

    public function test_unauthenticated_user_cannot_cancel_reservation(): void
    {
        $response = $this->deleteJson("/api/reservations/{$this->reservation->uuid}");
        $response->assertStatus(401);
    }
}
