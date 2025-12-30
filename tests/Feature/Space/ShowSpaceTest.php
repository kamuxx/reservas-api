<?php

namespace Tests\Feature\Space;

use App\Models\Space;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShowSpaceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $client;
    protected $spaceActive;
    protected $spaceInactive;
    protected $activeStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $clientRole = Role::create(['name' => 'user', 'description' => 'User']); // Factory expects 'user'
        $this->activeStatus = Status::create(['name' => 'active']);

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
        $pricingRuleId = Str::uuid()->toString();
        DB::table('pricing_rules')->insert([
            'uuid' => $pricingRuleId,
            'name' => 'Precio Base',
            'description' => 'Precio estándar por hora',
            'price_adjustment' => 0,
            'adjustment_type' => 'fixed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear espacios
        $this->spaceActive = Space::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Espacio Activo',
            'description' => 'Descripción del espacio activo',
            'capacity' => 10,
            'is_active' => true,
            'spaces_type_id' => $spaceTypeId,
            'pricing_rule_id' => $pricingRuleId,
            'status_id' => $this->activeStatus->uuid,
            'created_by' => $this->admin->uuid,
        ]);

        $this->spaceInactive = Space::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Espacio Inactivo',
            'description' => 'Descripción del espacio inactivo',
            'capacity' => 5,
            'is_active' => false,
            'spaces_type_id' => $spaceTypeId,
            'pricing_rule_id' => $pricingRuleId,
            'status_id' => $this->activeStatus->uuid,
            'created_by' => $this->admin->uuid,
        ]);
    }

    /** @test */
    public function any_user_can_view_active_space_details()
    {
        // Usuario no autenticado
        $response = $this->getJson("/api/spaces/{$this->spaceActive->uuid}");
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Detalle del espacio obtenido exitosamente',
                'data' => [
                    'uuid' => $this->spaceActive->uuid,
                    'name' => 'Espacio Activo'
                ]
            ]);

        // Usuario cliente
        $token = JWTAuth::fromUser($this->client);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->spaceActive->uuid}");
        $response->assertStatus(200);
    }

    /** @test */
    public function public_user_cannot_view_inactive_space_details()
    {
        $response = $this->getJson("/api/spaces/{$this->spaceInactive->uuid}");
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Espacio no encontrado'
            ]);
    }

    /** @test */
    public function client_user_cannot_view_inactive_space_details()
    {
        $token = JWTAuth::fromUser($this->client);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->spaceInactive->uuid}");
        
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Espacio no encontrado'
            ]);
    }

    /** @test */
    public function admin_can_view_inactive_space_details()
    {
        $token = JWTAuth::fromUser($this->admin);
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/spaces/{$this->spaceInactive->uuid}");
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'uuid' => $this->spaceInactive->uuid,
                    'is_active' => false
                ]
            ]);
    }

    /** @test */
    public function returns_404_if_space_does_not_exist()
    {
        $fakeUuid = Str::uuid()->toString();
        $response = $this->getJson("/api/spaces/{$fakeUuid}");
        
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Espacio no encontrado'
            ]);
    }
}
