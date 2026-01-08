<?php

namespace Tests\Unit\Models;

use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceNotFullyBookedScopeTest extends TestCase
{
    use RefreshDatabase;

    private $spaceType;
    private $status;
    private $pricingRule;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spaceType = SpaceType::create(['name' => 'Meeting Room']);
        $this->status = Status::create(['name' => 'Available']);
        $this->pricingRule = PricingRule::create([
            'name' => 'Hourly',
            'price_adjustment' => 100,
            'adjustment_type' => 'fixed',
        ]);
        
        $adminRole = Role::create(['name' => 'admin']);
        $userStatus = Status::create(['name' => 'active']);
        $this->user = User::factory()->create([
            'role_id' => $adminRole->uuid,
            'status_id' => $userStatus->uuid,
        ]);
    }

    public function test_scope_not_fully_booked_includes_spaces_without_reservations()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        $availableSpaces = Space::notFullyBooked($date)->get();

        $this->assertCount(1, $availableSpaces);
        $this->assertEquals($space->uuid, $availableSpaces->first()->uuid);
    }

    public function test_scope_not_fully_booked_includes_spaces_with_partial_reservations()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        
        // Create a partial reservation (less than 24 hours)
        \DB::table('reservation')->insert([
            'uuid' => (string) \Str::uuid(),
            'space_id' => $space->uuid,
            'reserved_by' => $this->user->uuid,
            'event_name' => 'Test Event',
            'event_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00', // 3 hours
            'status_id' => Status::where('name', 'confirmed')->first()->uuid ?? $this->status->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $availableSpaces = Space::notFullyBooked($date)->get();

        $this->assertCount(1, $availableSpaces);
        $this->assertEquals($space->uuid, $availableSpaces->first()->uuid);
    }

    public function test_scope_not_fully_booked_excludes_spaces_fully_booked_24h()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        
        // Create a full day reservation (24+ hours)
        \DB::table('reservation')->insert([
            'uuid' => (string) \Str::uuid(),
            'space_id' => $space->uuid,
            'reserved_by' => $this->user->uuid,
            'event_name' => 'Test Event Full Day',
            'event_date' => $date,
            'start_time' => '00:00:00',
            'end_time' => '24:00:00', // 24 hours
            'status_id' => Status::where('name', 'confirmed')->first()->uuid ?? $this->status->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $availableSpaces = Space::notFullyBooked($date)->get();

        $this->assertCount(0, $availableSpaces);
    }

public function test_scope_not_fully_booked_ignores_canceled_reservations()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        
        // Create a canceled status first
        $canceledStatus = Status::create(['name' => 'canceled']);
        
        // Test 1: Verify space is available without any reservations
        $availableSpacesInitially = Space::notFullyBooked($date)->get();
        $this->assertCount(1, $availableSpacesInitially);
        $this->assertEquals($space->uuid, $availableSpacesInitially->first()->uuid);
        
        // Create a canceled reservation for the full day
        \DB::table('reservation')->insert([
            'uuid' => (string) \Str::uuid(),
            'space_id' => $space->uuid,
            'reserved_by' => $this->user->uuid,
            'event_name' => 'Test Event Canceled',
            'event_date' => $date,
            'start_time' => '00:00:00',
            'end_time' => '24:00:00', // 24 hours
            'status_id' => $canceledStatus->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify that the reservation was created
        $reservations = \DB::table('reservation')->where('space_id', $space->uuid)->get();
        $this->assertCount(1, $reservations);
        $this->assertEquals($canceledStatus->uuid, $reservations->first()->status_id);
        
        // Test 2: Space should still be available because canceled reservations are ignored
        $availableSpaces = Space::notFullyBooked($date)->get();

        // The space should be available because canceled reservations are ignored
        $this->assertCount(1, $availableSpaces);
        $this->assertEquals($space->uuid, $availableSpaces->first()->uuid);
    }

    public function test_scope_not_fully_booked_ignores_deleted_reservations()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $date = Carbon::now()->format('Y-m-d');
        
        // Create a full day reservation but deleted
        \DB::table('reservation')->insert([
            'uuid' => (string) \Str::uuid(),
            'space_id' => $space->uuid,
            'reserved_by' => $this->user->uuid,
            'event_name' => 'Test Event Deleted',
            'event_date' => $date,
            'start_time' => '00:00:00',
            'end_time' => '24:00:00', // 24 hours
            'status_id' => Status::where('name', 'confirmed')->first()->uuid ?? $this->status->uuid,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => now(),
        ]);

        $availableSpaces = Space::notFullyBooked($date)->get();

        $this->assertCount(1, $availableSpaces);
        $this->assertEquals($space->uuid, $availableSpaces->first()->uuid);
    }

    public function test_scope_not_fully_booked_filters_by_specific_date()
    {
        $space = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->format('Y-m-d');
        $otherDate = Carbon::now()->addDay()->format('Y-m-d');
        
        // Create a full day reservation for a different date
        \DB::table('reservation')->insert([
            'uuid' => (string) \Str::uuid(),
            'space_id' => $space->uuid,
            'reserved_by' => $this->user->uuid,
            'event_name' => 'Test Event Other Date',
            'event_date' => $otherDate,
            'start_time' => '00:00:00',
            'end_time' => '24:00:00', // 24 hours
            'status_id' => Status::where('name', 'confirmed')->first()->uuid ?? $this->status->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $availableSpaces = Space::notFullyBooked($targetDate)->get();

        $this->assertCount(1, $availableSpaces);
        $this->assertEquals($space->uuid, $availableSpaces->first()->uuid);
    }
}