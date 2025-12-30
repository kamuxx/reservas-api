<?php

namespace Tests\Feature\Space;

use App\Models\Space;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckSpaceAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;
    protected $space;
    protected $activeStatus;
    protected $confirmedStatus;
    protected $pricingRuleId;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $clientRole = Role::create(['name' => 'user', 'description' => 'User']);
        $this->activeStatus = Status::create(['name' => 'active']);
        $this->confirmedStatus = Status::create(['name' => 'confirmada']);

        // Crear usuarios
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->uuid,
            'status_id' => $this->activeStatus->uuid,
        ]);
        $this->client = User::factory()->create([
            'role_id' => $clientRole->uuid,
            'status_id' => $this->activeStatus->uuid,
        ]);

        // Crear tipo de espacio
        $spaceTypeId = Str::uuid()->toString();
        DB::table('space_types')->insert([
            'uuid' => $spaceTypeId,
            'name' => 'Sala de Conferencias',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear regla de precio
        $this->pricingRuleId = Str::uuid()->toString();
        DB::table('pricing_rules')->insert([
            'uuid' => $this->pricingRuleId,
            'name' => 'Precio Base',
            'description' => 'Precio estándar por hora',
            'price_adjustment' => 0,
            'adjustment_type' => 'fixed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear espacio
        $this->space = Space::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Espacio de Prueba',
            'description' => 'Descripción del espacio de prueba',
            'capacity' => 10,
            'is_active' => true,
            'spaces_type_id' => $spaceTypeId,
            'pricing_rule_id' => $this->pricingRuleId,
            'status_id' => $this->activeStatus->uuid,
            'created_by' => $this->admin->uuid,
        ]);
    }

    /** @test */
    public function authenticated_user_can_check_space_availability()
    {
        $token = JWTAuth::fromUser($this->client);

        // Crear una reserva para hoy
        Reservation::create([
            'reserved_by' => $this->client->uuid,
            'space_id' => $this->space->uuid,
            'status_id' => $this->confirmedStatus->uuid,
            'event_name' => 'Evento de prueba',
            'event_date' => now()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'pricing_rule_id' => $this->pricingRuleId,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->space->uuid}/availability?start_date=" . now()->format('Y-m-d') . "&end_date=" . now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Disponibilidad del espacio obtenida exitosamente',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['event_date', 'start_time', 'end_time']
                ]
            ]);
        
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function unauthenticated_user_cannot_check_availability()
    {
        $response = $this->getJson("/api/spaces/{$this->space->uuid}/availability?start_date=2025-01-01&end_date=2025-01-01");
        $response->assertStatus(401);
    }

    /** @test */
    public function validates_required_dates()
    {
        $token = JWTAuth::fromUser($this->client);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->space->uuid}/availability");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function validates_date_format()
    {
        $token = JWTAuth::fromUser($this->client);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->space->uuid}/availability?start_date=invalid-date&end_date=2025-01-01");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function validates_end_date_after_start_date()
    {
        $token = JWTAuth::fromUser($this->client);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->space->uuid}/availability?start_date=2025-01-02&end_date=2025-01-01");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function returns_404_if_space_not_found()
    {
        $token = JWTAuth::fromUser($this->client);
        $fakeUuid = Str::uuid()->toString();
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$fakeUuid}/availability?start_date=2025-01-01&end_date=2025-01-01");

        $response->assertStatus(404);
    }
}
