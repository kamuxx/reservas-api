<?php

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

class ReservationAccessTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $admin;
    private $otherUser;
    private $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $roleUser = Role::create(['name' => 'user', 'uuid' => (string) Str::uuid()]);
        $roleAdmin = Role::create(['name' => 'admin', 'uuid' => (string) Str::uuid()]);

        // Setup Users
        $this->user = User::factory()->create(['role_id' => $roleUser->id]);
        $this->admin = User::factory()->create(['role_id' => $roleAdmin->id]);
        $this->otherUser = User::factory()->create(['role_id' => $roleUser->id]);

        // Setup Dependencies
        $status = Status::create(['name' => 'confirmada', 'uuid' => 'status-confirmada']);
        $spaceType = SpaceType::create(['name' => 'Meeting Room', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Standard',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);

        // Create status-active as well since space creation relies on it being valid if we don't mock?
        // Actually, schema constraints fail if it doesn't exist.
        Status::create(['name' => 'active', 'uuid' => 'status-active']);

        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Space',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-active', // Mock if needed or create
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $this->admin->uuid,
        ]);

        // Setup Reservation
        $this->reservation = Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->user->uuid,
            'space_id' => $space->uuid,
            'status_id' => $status->uuid,
            'event_name' => 'My Event',
            'event_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 20.00,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        // Ensure View exists or mock if possible (In Feature tests we use real DB usually)
        // Since we refreshed DB, the view should be there if migration ran. 
        // Note: The view depends on the tables. 
    }

    // --- List Tests ---

    public function test_list_reservations_success()
    {
        Passport::actingAs($this->user);

        $response = $this->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'reservation_uuid',
                        'event_name',
                        'event_date',
                        'start_time',
                        'end_time',
                        'event_price',
                        'status_name'
                    ]
                ]
            ]);

        // Check if our reservation is in the list
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->reservation->uuid, $data[0]['reservation_uuid']);
    }

    public function test_list_reservations_empty()
    {
        Passport::actingAs($this->otherUser);

        $response = $this->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_list_reservations_unauthorized()
    {
        $response = $this->getJson('/api/reservations');

        $response->assertStatus(401);
    }

    // --- Detail Tests ---

    public function test_show_reservation_success_owner()
    {
        Passport::actingAs($this->user);

        $response = $this->getJson('/api/reservations/' . $this->reservation->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'reservation_uuid' => $this->reservation->uuid,
                    'user_uuid' => $this->user->uuid,
                ]
            ]);
    }

    public function test_show_reservation_success_admin()
    {
        Passport::actingAs($this->admin);

        $response = $this->getJson('/api/reservations/' . $this->reservation->uuid);

        $response->assertStatus(200);
    }

    public function test_show_reservation_forbidden_other_user()
    {
        Passport::actingAs($this->otherUser);

        $response = $this->getJson('/api/reservations/' . $this->reservation->uuid);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para ver esta reserva.'
            ]);
    }

    public function test_show_reservation_not_found()
    {
        Passport::actingAs($this->user);

        $response = $this->getJson('/api/reservations/non-existent-uuid');

        $response->assertStatus(404);
    }
}
