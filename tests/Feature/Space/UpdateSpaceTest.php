<?php

namespace Tests\Feature\Space;

use App\Models\PricingRule;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\UserAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateSpaceTest extends TestCase
{

    const LOGIN_ROUTE = '/api/auth/login';
    const SPACE_UPDATE_ROUTE = '/api/spaces/{uuid}';
    /**
     * A basic feature test example.
     */
    public function test_valid_route(): void
    {
        $uuid = Str::uuid()->toString();
        $route = str_replace('{uuid}', $uuid, self::SPACE_UPDATE_ROUTE);
        $response = $this->putJson($route);

        $this->assertNotEquals(404, $response->status(), "La ruta no existe");
    }

    public function test_update_with_valid_data(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $token = $content['data']['access_token'];

        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $spaceData = [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ];

        $space = Space::create($spaceData);
        $uuid = $space->uuid;
        $updateData = [
            'name' => 'Space 2',
            'description' => 'Description 2',
            'capacity' => 20,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => false,
        ];


        $route = str_replace('{uuid}', $uuid, self::SPACE_UPDATE_ROUTE);
        $response = $this->withToken($token, 'Bearer')->putJson($route, $updateData);
        $response->assertStatus(200);
    }

    /**
     * TP-HU006-002: FA-001 - Nombre duplicado al cambiar
     */
    public function test_update_fails_when_name_already_exists_in_another_space(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $token = $content['data']['access_token'];

        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $spaceData = [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => rand(1, 100),
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ];

        $space = Space::create($spaceData);
        $spaceData = [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => rand(1, 100),
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ];

        $space2 = Space::create($spaceData);
        $uuid = $space2->uuid;
        $updateData = [
            'name' => $space->name,
            'description' => 'Description 2',
            'capacity' => 20,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => false,
        ];


        $route = str_replace('{uuid}', $uuid, self::SPACE_UPDATE_ROUTE);
        $response = $this->withToken($token, 'Bearer')->putJson($route, $updateData);
        $response->assertStatus(422);
    }

    /**
     * TP-HU006-003: FA-002 - Espacio no encontrado
     */
    public function test_update_fails_when_space_does_not_exist(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $token = $content['data']['access_token'];

        $uuid = Str::uuid()->toString();
        $route = str_replace('{uuid}', $uuid, self::SPACE_UPDATE_ROUTE);
        $response = $this->withToken($token, 'Bearer')->putJson($route);

        $this->assertEquals(404, $response->status());
    }

    /**
     * TP-HU006-004: FS-001 - Usuario no administrador
     */
    public function test_update_fails_when_user_is_not_admin(): void
    {
        $password = 'Tost@123';
        $user = User::factory()->create();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $user->email,
            "password" => $password
        ]);

        $response->assertStatus(200);

        $content = $response->json();
        $token = $content['data']['access_token'];

        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $spaceData = [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => rand(1, 100),
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $user->uuid,
        ];

        $space = Space::create($spaceData);

        $uuid = $space->uuid;
        $route = str_replace('{uuid}', $uuid, self::SPACE_UPDATE_ROUTE);

        $updateData = [
            'name' => $space->name,
            'description' => 'Description 2',
            'capacity' => 20,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => false,
        ];
        $response = $this->withToken($token, 'Bearer')->putJson($route, $updateData);

        $this->assertEquals(403, $response->status());
    }



    /**
     * Test: Validación de campos requeridos (si aplica) o formatos inválidos
     */
    public function test_update_fails_with_invalid_data_types(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $token = $response->json()['data']['access_token'];

        $space = Space::factory()->create();
        $route = str_replace('{uuid}', $space->uuid, self::SPACE_UPDATE_ROUTE);

        $invalidData = [
            'name' => 123, // debe ser string
            'capacity' => 'muchos', // debe ser integer
            'is_active' => 'si', // debe ser boolean
        ];

        $response = $this->withToken($token, 'Bearer')->putJson($route, $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'capacity', 'is_active']);
    }

    /**
     * Test: Validación de existencia de llaves foráneas (space_type, status, pricing_rule)
     */
    public function test_update_fails_when_foreign_keys_do_not_exist(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $token = $response->json()['data']['access_token'];

        $space = Space::factory()->create();
        $route = str_replace('{uuid}', $space->uuid, self::SPACE_UPDATE_ROUTE);

        $invalidData = [
            'spaces_type_id' => Str::uuid()->toString(),
            'status_id' => Str::uuid()->toString(),
            'pricing_rule_id' => Str::uuid()->toString(),
        ];

        $response = $this->withToken($token, 'Bearer')->putJson($route, $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['spaces_type_id', 'status_id', 'pricing_rule_id']);
    }

    /**
     * Test: El nombre del espacio puede mantenerse igual para el mismo registro (Ignora el UUID actual)
     */
    public function test_update_success_when_keeping_the_same_name(): void
    {
        $password = 'Admin@123';
        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();
        $response = $this->postJson(self::LOGIN_ROUTE, [
            "email" => $admin->email,
            "password" => $password
        ]);

        $token = $response->json()['data']['access_token'];

        $space = Space::factory()->create(['name' => 'Original Name']);
        $route = str_replace('{uuid}', $space->uuid, self::SPACE_UPDATE_ROUTE);

        $updateData = [
            'name' => 'Original Name', // Mismo nombre
            'description' => 'Updated Description'
        ];

        $response = $this->withToken($token, 'Bearer')->putJson($route, $updateData);

        $response->assertStatus(200);
        $this->assertEquals('Original Name', $space->fresh()->name);
        $this->assertEquals('Updated Description', $space->fresh()->description);
    }
}
