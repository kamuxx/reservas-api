<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Repositories\SpaceRepository;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class SpaceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $spaceType;
    private $status;
    private $pricingRule;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->spaceType = SpaceType::create(['name' => 'Sala', 'uuid' => (string) Str::uuid()]);
        $this->status = Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        $this->pricingRule = PricingRule::create([
            'name' => 'Rule', 
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed'
        ]);
        $this->user = \App\Models\User::factory()->create();
    }

    public function test_all_returns_collection(): void
    {
        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space 1',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'created_by' => $this->user->uuid,
        ]);

        $result = SpaceRepository::all();
        $this->assertCount(1, $result);
    }

    public function test_search_returns_array(): void
    {
        $uuid = (string) Str::uuid();
        Space::create([
            'uuid' => $uuid,
            'name' => 'Space 1',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'created_by' => $this->user->uuid,
        ]);

        $result = SpaceRepository::search(['uuid' => $uuid]);
        $this->assertIsArray($result);
        $this->assertEquals('Space 1', $result[0]['name']);
    }

    public function test_paginate(): void
    {
        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space 1',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->user->uuid,
        ]);

        $result = SpaceRepository::paginate(['capacity' => 5, 'is_active' => true], 5);
        $this->assertEquals(1, $result->total());
    }
}
