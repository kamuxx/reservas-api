<?php

namespace Tests\Feature\Admin;

use App\Models\Space;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SpaceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\StatusSeeder::class);
        $this->seed(\Database\Seeders\SpaceTypeSeeder::class);
        $this->seed(\Database\Seeders\PricingRuleSeeder::class); // Ensure pricing rules exist

        // Create Admin
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create User
        $userRole = Role::where('name', 'user')->first();
        $this->user = User::factory()->create(['role_id' => $userRole->id]);
    }

    public function test_admin_can_list_spaces()
    {
        Space::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/spaces');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_non_admin_cannot_list_spaces()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/admin/spaces');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_space()
    {
        $payload = [
            'name' => 'New Admin Space',
            'description' => 'Test Description',
            'capacity' => 10,
            'spaces_type_id' => \App\Models\SpaceType::first()->uuid,
            'status_id' => \App\Models\Status::first()->uuid,
            'pricing_rule_id' => \App\Models\PricingRule::first()->uuid,
            'location_id' => null // Optional for now
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/spaces', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Admin Space']);

        $this->assertDatabaseHas('spaces', ['name' => 'New Admin Space']);
    }

    public function test_admin_can_update_space()
    {
        $space = Space::factory()->create();

        $payload = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/spaces/{$space->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('spaces', ['uuid' => $space->uuid, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_space()
    {
        $space = Space::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/spaces/{$space->uuid}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('spaces', ['uuid' => $space->uuid]);
    }
}
