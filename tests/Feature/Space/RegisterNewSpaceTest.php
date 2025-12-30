<?php

namespace Tests\Feature\Space;

use Tests\TestCase;
use App\Models\User;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use App\Models\Space;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Database\Seeders\UserAdminSeeder;

class RegisterNewSpaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_valid_route(): void
    {
        $response = $this->postJson('/api/spaces');
        $this->assertNotEquals($response->status(), 404);
    }

    public function test_invalid_route_method(): void
    {
        $this->seed(UserAdminSeeder::class);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);

        $response = $this->withToken($content['data']['access_token'])
            ->patchJson('/api/spaces');
        $response->assertStatus(405);
    }

    public function test_unauthorized_route(): void
    {
        $response = $this->postJson('/api/spaces');
        $response->assertStatus(401);
    }

    public function test_register_new_space_with_valid_data(): void
    {

        $this->seed(UserAdminSeeder::class);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);


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
        ];

        $response = $this->postJson('/api/spaces', $spaceData, [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $response->assertStatus(201);

        // Verify response structure and data
        $response->assertJsonStructure([
            'data' => [
                'uuid',
                'name',
                'description',
                'capacity',
                'spaces_type_id',
                'status_id',
                'pricing_rule_id',
                'is_active',
            ]
        ]);

        $response->assertJsonFragment([
            'name' => 'Space 1',
            'capacity' => 10,
        ]);

        // Verify database persistence
        $this->assertDatabaseHas('spaces', [
            'name' => 'Space 1',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
        ]);

        // Verify audit trail
        $this->assertDatabaseHas('entity_audit_trails', [
            'entity_name' => 'spaces',
            'operation' => 'create',
        ]);
    }

    public function test_register_new_space_with_duplicated_name(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);


        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        Space::create([
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 1,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ]);

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 1,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $this->withExceptionHandling();
        $response->assertStatus(422);

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('name', $content['errors']);
    }

    public function test_register_new_space_with_invalid_capacity(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);


        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 0,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $this->withExceptionHandling();
        $response->assertStatus(422);

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('capacity', $content['errors']);
    }

    public function test_register_new_space_with_invalid_spaces_type_id(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);


        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 10,
            'spaces_type_id' => Str::uuid()->toString(),
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
            'created_by' => $admin->uuid,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $response->assertStatus(422);

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('spaces_type_id', $content['errors']);
    }

    public function test_register_new_space_with_invalid_status_id(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $spaceType = SpaceType::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => Str::uuid()->toString(),
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status_id']);
    }

    public function test_register_new_space_with_invalid_pricing_rule_id(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => Str::uuid()->toString(),
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pricing_rule_id']);
    }

    public function test_register_new_space_with_fields_empty(): void
    {

        $this->seed(UserAdminSeeder::class);
        $admin = User::where('email', 'admin@admin.com')->first();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'Admin@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('access_token', $content['data']);
        $this->assertIsString($content['data']['access_token']);


        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space 1',
            'description' => 'Description 1',
            'capacity' => '',
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $admin->uuid,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $this->withExceptionHandling();
        $response->assertStatus(422);

        $content = $response->json();
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('capacity', $content['errors']);
    }

    public function test_register_new_space_with_non_admin_user(): void
    {
        $user = User::factory()->create();
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Tost@123',
        ]);

        $loginResponse->assertStatus(200);
        $content = $loginResponse->json();

        $spaceType = SpaceType::inRandomOrder()->first();
        $status = Status::inRandomOrder()->first();
        $pricingRule = PricingRule::inRandomOrder()->first();

        $response = $this->postJson('/api/spaces', [
            'name' => 'Space Unauth',
            'description' => 'Description 1',
            'capacity' => 1,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $content['data']['access_token'],
        ]);

        $this->withExceptionHandling();
        $response->assertStatus(403);
    }
}
