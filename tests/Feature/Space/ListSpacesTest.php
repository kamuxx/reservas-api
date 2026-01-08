<?php

namespace Tests\Feature\Space;

use App\Models\PricingRule;
use App\Models\Role;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ListSpacesTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $spaceType;
    private $status;
    private $pricingRule;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);
        $activeStatus = Status::create(['name' => 'active']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->uuid,
            'status_id' => $activeStatus->uuid,
        ]);

        $this->user = User::factory()->create([
            'role_id' => $userRole->uuid,
            'status_id' => $activeStatus->uuid,
        ]);

        $this->spaceType = SpaceType::create(['name' => 'Meeting Room']);
        $this->status = Status::create(['name' => 'Available']);
        $this->pricingRule = PricingRule::create([
            'name' => 'Hourly',
            'price_adjustment' => 50,
            'adjustment_type' => 'fixed',
        ]);
    }

    public function test_public_user_can_list_only_active_spaces(): void
    {
        // Create 2 active spaces and 1 inactive space
        Space::factory()->count(2)->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => false,
        ]);

        $response = $this->getJson(route('spaces.index'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.total', 2);
    }

    public function test_admin_user_can_list_all_spaces(): void
    {
        Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => false,
        ]);

        $token = JWTAuth::fromUser($this->admin);

        $response = $this->getJson(route('spaces.index'), [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.total', 2);
    }

    public function test_can_filter_spaces_by_capacity(): void
    {
        Space::factory()->create([
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        Space::factory()->create([
            'capacity' => 20,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('spaces.index', ['capacity' => 15]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.capacity', 20);
    }

    public function test_can_filter_spaces_by_type(): void
    {
        $otherType = SpaceType::create(['name' => 'Coworking']);

        Space::factory()->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        Space::factory()->create([
            'spaces_type_id' => $otherType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('spaces.index', ['spaces_type_id' => $otherType->uuid]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.spaces_type_id', $otherType->uuid);
    }

    public function test_pagination_works(): void
    {
        Space::factory()->count(15)->create([
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('spaces.index', ['per_page' => 5]));

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data')
            ->assertJsonPath('data.per_page', 5)
            ->assertJsonPath('data.total', 15);
    }

    public function test_returns_empty_list_when_no_results_match_filters(): void
    {
        Space::factory()->create([
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->status->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('spaces.index', ['capacity' => 100]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.total', 0);
    }

    public function test_returns_422_when_parameters_are_invalid(): void
    {
        $response = $this->getJson(route('spaces.index', ['capacity' => 'abc']));
        $response->assertStatus(422);

        $response = $this->getJson(route('spaces.index', ['page' => -1]));
        $response->assertStatus(422);
    }

    public function test_can_search_spaces_by_name(): void
    {
        $this->markTestIncomplete('Pending implementation: Search by name/description');
    }

    public function test_admin_can_filter_spaces_by_status(): void
    {
        $this->markTestIncomplete('Pending implementation: Admin filtering by status_id');
    }

    public function test_response_includes_relations_features_and_images(): void
    {
        $this->markTestIncomplete('Pending implementation: Verify features and images are included in the response');
    }
}
