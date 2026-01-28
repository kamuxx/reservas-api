<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
        $this->seed(\Database\Seeders\PricingRuleSeeder::class);
        // Minimal seed for stats
        Space::factory()->count(3)->create();

        // Create Admin
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create User
        $userRole = Role::where('name', 'user')->first();
        $this->user = User::factory()->create(['role_id' => $userRole->id]);
    }

    public function test_admin_can_access_dashboard_stats()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'kpi' => ['total_spaces', 'total_reservations_month', 'estimated_revenue'],
                'occupancy_chart',
                'status_chart'
            ]);
    }

    public function test_non_admin_cannot_access_dashboard_stats()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }
}
