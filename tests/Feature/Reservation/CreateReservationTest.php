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

class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $space;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $roleUser = Role::firstOrCreate(['name' => 'user'], ['uuid' => (string) Str::uuid()]);
        
        // Crear estados
        $statusActive = Status::firstOrCreate(['name' => 'active'], ['uuid' => (string) Str::uuid()]);
        Status::firstOrCreate(['name' => 'agendada'], ['uuid' => (string) Str::uuid()]);
        Status::firstOrCreate(['name' => 'confirmada'], ['uuid' => (string) Str::uuid()]);
        Status::firstOrCreate(['name' => 'cancelada'], ['uuid' => (string) Str::uuid()]);

        // Crear usuario
        $this->user = User::factory()->create([
            'role_id' => $roleUser->uuid,
            'status_id' => $statusActive->uuid,
        ]);

        // Login para obtener token
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'Tost@123',
        ]);
        $this->token = $response->json('data.access_token');

        // Crear tipo de espacio y regla de precio
        $spaceType = SpaceType::firstOrCreate(['name' => 'Sala'], ['uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::firstOrCreate(['name' => 'Estándar'], [
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10.00,
            'adjustment_type' => 'fixed',
        ]);

        // Crear espacio
        $this->space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala ' . Str::random(5), // Evitar duplicados de nombre si hay otros tests
            'description' => 'Descripción',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $statusActive->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $this->user->uuid,
        ]);
    }

    public function test_authenticated_user_can_create_reservation(): void
    {
        $reservationData = [
            'space_id' => $this->space->uuid,
            'event_name' => 'Mi Evento',
            'event_description' => 'Descripción del evento',
            'event_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/reservations', $reservationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'uuid',
                    'event_name',
                    'event_price'
                ]
            ]);

        $this->assertDatabaseHas('reservation', [
            'event_name' => 'Mi Evento',
            'space_id' => $this->space->uuid,
        ]);
    }

    public function test_cannot_create_overlapping_reservation(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        // Crear una reserva existente
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->user->uuid,
            'space_id' => $this->space->uuid,
            'status_id' => Status::where('name', 'agendada')->first()->uuid,
            'event_name' => 'Evento Existente',
            'event_date' => $date,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 20.00,
            'pricing_rule_id' => $this->space->pricing_rule_id,
        ]);

        // Intentar crear una reserva que traslapa
        $reservationData = [
            'space_id' => $this->space->uuid,
            'event_name' => 'Evento Nuevo',
            'event_date' => $date,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/reservations', $reservationData);

        $response->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'message' => 'El espacio ya se encuentra reservado en el horario seleccionado.'
            ]);
    }

    public function test_validation_errors_when_creating_reservation(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['space_id', 'event_name', 'event_date', 'start_time', 'end_time']);
    }

    public function test_unauthenticated_user_cannot_create_reservation(): void
    {
        // Forzar logout para esta prueba específica
        auth('api')->logout();

        $reservationData = [
            'space_id' => $this->space->uuid,
            'event_name' => 'Mi Evento',
            'event_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ];

        $response = $this->postJson('/api/reservations', $reservationData);
        $response->assertStatus(401);
    }
}
