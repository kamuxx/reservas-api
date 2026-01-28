<?php

namespace Tests\Unit\Repositories;

use App\Models\Location;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Repositories\SpaceRepository;
use Tests\TestCase;

class SpaceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure core data exists
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\StatusSeeder::class);
        $this->seed(\Database\Seeders\SpaceTypeSeeder::class);
        $this->seed(\Database\Seeders\PricingRuleSeeder::class);
    }

    public function test_paginate_admin_returns_paginated_results_with_eager_loading()
    {
        $admin = User::factory()->create();
        $location = Location::factory()->create();
        $type = SpaceType::first();
        $status = Status::where('name', 'active')->first();
        $rule = PricingRule::first();

        // Create 20 spaces
        Space::factory()->count(20)->create([
            'created_by' => $admin->uuid,
            'spaces_type_id' => $type->uuid,
            'status_id' => $status->uuid,
            'location_id' => $location->uuid,
            'pricing_rule_id' => $rule->uuid,
        ]);

        $results = SpaceRepository::paginateAdmin([], 10);

        $this->assertEquals(20, $results->total());
        $this->assertEquals(10, $results->perPage());
        $this->assertTrue($results->first()->relationLoaded('spaceType'));
        $this->assertTrue($results->first()->relationLoaded('location'));
        $this->assertTrue($results->first()->relationLoaded('pricingRule'));
        $this->assertTrue($results->first()->relationLoaded('status'));
    }

    public function test_has_active_reservations()
    {
        // 1. Setup Space
        $space = Space::factory()->create();
        $user = User::factory()->create(['role_id' => 1]); // Adjust role logic if needed

        // 2. Create Confirmed Reservation
        $confirmedStatus = Status::where('name', 'confirmada')->first();
        if (!$confirmedStatus) {
            // Fallback if seeder didn't create 'confirmada'
            $confirmedStatus = Status::factory()->create(['name' => 'confirmada', 'uuid' => Str::uuid()]);
        }

        Reservation::factory()->create([
            'space_id' => $space->uuid,
            'status_id' => $confirmedStatus->uuid,
            'event_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00'
        ]);

        // 3. Test True
        $this->assertTrue(SpaceRepository::hasActiveReservations($space->uuid));

        // 4. Test False (Cancel reservation)
        $canceledStatus = Status::where('name', 'cancelada')->first();
        if (!$canceledStatus) {
            $canceledStatus = Status::factory()->create(['name' => 'cancelada', 'uuid' => Str::uuid()]);
        }

        Reservation::where('space_id', $space->uuid)->update(['status_id' => $canceledStatus->uuid]);

        $this->assertFalse(SpaceRepository::hasActiveReservations($space->uuid));
    }

    public function test_get_dashboard_stats()
    {
        // Create dummy data
        Space::factory()->count(5)->create();

        // This test might fail if getDashboardStats logic depends on specific reservation dates seeded
        // For now, we just assert the array structure
        $stats = SpaceRepository::getDashboardStats();

        $this->assertArrayHasKey('kpi', $stats);
        $this->assertArrayHasKey('occupancy_chart', $stats);
        $this->assertArrayHasKey('status_chart', $stats);
        $this->assertArrayHasKey('total_spaces', $stats['kpi']);
    }
}
