<?php

namespace Tests\Unit\Models;

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

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_relationships(): void
    {
        $role = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $status = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $user = User::factory()->create(['role_id' => $role->uuid, 'status_id' => $status->uuid]);
        
        $spaceType = SpaceType::create(['name' => 'Type 1', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Rule 1', 
            'uuid' => (string) Str::uuid(), 
            'adjustment_type' => 'fixed', 
            'price_adjustment' => 10
        ]);
        
        $space = Space::factory()->create([
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid
        ]);

        $reservation = Reservation::factory()->create([
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $this->assertInstanceOf(User::class, $reservation->user);
        $this->assertInstanceOf(Space::class, $reservation->space);
        $this->assertInstanceOf(Status::class, $reservation->status);
        $this->assertInstanceOf(PricingRule::class, $reservation->pricingRule);
    }

    public function test_space_relationships_and_route_key(): void
    {
        $role = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $status = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $user = User::factory()->create(['role_id' => $role->uuid, 'status_id' => $status->uuid]);
        
        $spaceType = SpaceType::create(['name' => 'Type 1', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Rule 1', 
            'uuid' => (string) Str::uuid(), 
            'adjustment_type' => 'fixed', 
            'price_adjustment' => 10
        ]);
        
        $space = Space::factory()->create([
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $status->uuid,
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid
        ]);

        $this->assertInstanceOf(SpaceType::class, $space->spaceType);
        $this->assertInstanceOf(Status::class, $space->status);
        $this->assertInstanceOf(PricingRule::class, $space->pricingRule);
        $this->assertEquals('uuid', $space->getRouteKeyName());
    }

    public function test_user_relationships(): void
    {
        $role = Role::where('name', 'user')->first() ?: Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $status = Status::where('name', 'active')->first() ?: Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $user = User::factory()->create(['role_id' => $role->uuid, 'status_id' => $status->uuid]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertInstanceOf(Status::class, $user->status);
        $this->assertTrue($user->isAdmin() === false);
    }
}
