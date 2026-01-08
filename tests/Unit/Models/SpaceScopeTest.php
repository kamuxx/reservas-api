<?php

namespace Tests\Unit\Models;

use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceScopeTest extends TestCase
{
    use RefreshDatabase;

    private $spaceType;
    private $status;
    private $pricingRule;

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
    }

    public function test_scope_active_filters_only_active_spaces()
    {
        $activeSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $inactiveSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => false,
        ]);

        $activeSpaces = Space::active()->get();

        $this->assertCount(1, $activeSpaces);
        $this->assertEquals($activeSpace->uuid, $activeSpaces->first()->uuid);
    }

    public function test_scope_by_type_filters_by_space_type()
    {
        $otherType = SpaceType::create(['name' => 'Coworking']);

        $meetingRoom = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $coworkingSpace = Space::factory()->create([
            'spaces_type_id' => $otherType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $filteredSpaces = Space::byType($this->spaceType->uuid)->get();

        $this->assertCount(1, $filteredSpaces);
        $this->assertEquals($meetingRoom->uuid, $filteredSpaces->first()->uuid);
    }

    public function test_scope_by_type_returns_all_when_null_type_id()
    {
        Space::factory()->count(2)->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $allSpaces = Space::byType(null)->get();

        $this->assertCount(2, $allSpaces);
    }

    public function test_scope_by_min_capacity_filters_by_capacity()
    {
        $smallSpace = Space::factory()->create([
            'capacity' => 5,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $largeSpace = Space::factory()->create([
            'capacity' => 20,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $filteredSpaces = Space::byMinCapacity(10)->get();

        $this->assertCount(1, $filteredSpaces);
        $this->assertEquals($largeSpace->uuid, $filteredSpaces->first()->uuid);
    }

    public function test_scope_by_min_capacity_returns_all_when_null_capacity()
    {
        Space::factory()->count(2)->create([
            'capacity' => 5,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $allSpaces = Space::byMinCapacity(null)->get();

        $this->assertCount(2, $allSpaces);
    }

    public function test_scope_by_price_range_filters_by_min_price()
    {
        $cheapPricing = PricingRule::create([
            'name' => 'Cheap',
            'price_adjustment' => 50,
            'adjustment_type' => 'fixed',
        ]);

        $expensivePricing = PricingRule::create([
            'name' => 'Expensive',
            'price_adjustment' => 200,
            'adjustment_type' => 'fixed',
        ]);

        $cheapSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $cheapPricing->uuid,
            'is_active' => true,
        ]);

        $expensiveSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $expensivePricing->uuid,
            'is_active' => true,
        ]);

        $filteredSpaces = Space::byPriceRange(100, null)->get();

        $this->assertCount(1, $filteredSpaces);
        $this->assertEquals(200, $filteredSpaces->first()->pricingRule->price_adjustment);
    }

    public function test_scope_by_price_range_filters_by_max_price()
    {
        $cheapPricing = PricingRule::create([
            'name' => 'Cheap',
            'price_adjustment' => 50,
            'adjustment_type' => 'fixed',
        ]);

        $expensivePricing = PricingRule::create([
            'name' => 'Expensive',
            'price_adjustment' => 200,
            'adjustment_type' => 'fixed',
        ]);

        $cheapSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $cheapPricing->uuid,
            'is_active' => true,
        ]);

        $expensiveSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $expensivePricing->uuid,
            'is_active' => true,
        ]);

        $filteredSpaces = Space::byPriceRange(null, 100)->get();

        $this->assertCount(1, $filteredSpaces);
        $this->assertEquals(50, $filteredSpaces->first()->pricingRule->price_adjustment);
    }

    public function test_scope_by_price_range_filters_by_range()
    {
        $cheapPricing = PricingRule::create([
            'name' => 'Cheap',
            'price_adjustment' => 50,
            'adjustment_type' => 'fixed',
        ]);

        $mediumPricing = PricingRule::create([
            'name' => 'Medium',
            'price_adjustment' => 100,
            'adjustment_type' => 'fixed',
        ]);

        $expensivePricing = PricingRule::create([
            'name' => 'Expensive',
            'price_adjustment' => 200,
            'adjustment_type' => 'fixed',
        ]);

        $cheapSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $cheapPricing->uuid,
            'is_active' => true,
        ]);

        $mediumSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $mediumPricing->uuid,
            'is_active' => true,
        ]);

        $expensiveSpace = Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $expensivePricing->uuid,
            'is_active' => true,
        ]);

        $filteredSpaces = Space::byPriceRange(75, 150)->get();

        $this->assertCount(1, $filteredSpaces);
        $this->assertEquals(100, $filteredSpaces->first()->pricingRule->price_adjustment);
    }

    public function test_scope_by_price_range_returns_all_when_null_prices()
    {
        Space::factory()->count(2)->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $allSpaces = Space::byPriceRange(null, null)->get();

        $this->assertCount(2, $allSpaces);
    }
}