<?php

namespace Tests\Unit\Repositories;

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Repositories\ReservationRepository;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\Status;
use App\Models\User;
use App\Models\SpaceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $space;
    private $user;
    private $statusConfirmada;
    private $statusCancelada;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala', 'uuid' => (string) Str::uuid()]);
        $statusActive = Status::create(['name' => 'active', 'uuid' => (string) Str::uuid()]);
        
        $this->statusConfirmada = Status::create(['name' => 'confirmada', 'uuid' => (string) Str::uuid()]);
        $this->statusCancelada = Status::create(['name' => 'cancelada', 'uuid' => (string) Str::uuid()]);

        $this->space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Space',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => $statusActive->uuid,
            'created_by' => $this->user->uuid,
        ]);
    }

    public function test_create_reservation(): void
    {
        $data = [
            'uuid' => (string) Str::uuid(),
            'space_id' => $this->space->uuid,
            'reserved_by' => $this->user->uuid,
            'status_id' => $this->statusConfirmada->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'event_price' => 100,
        ];

        $result = ReservationRepository::create($data);
        $this->assertInstanceOf(Reservation::class, $result);
        $this->assertEquals('Meeting', $result->event_name);
        $this->assertDatabaseHas('reservation', ['event_name' => 'Meeting']);
    }

    public function test_get_occupied_slots(): void
    {
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'space_id' => $this->space->uuid,
            'reserved_by' => $this->user->uuid,
            'status_id' => $this->statusConfirmada->uuid,
            'event_name' => 'Meeting 1',
            'event_date' => '2025-01-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'event_price' => 50,
        ]);

        $result = ReservationRepository::getOccupiedSlots($this->space->uuid, '2025-01-01', '2025-01-01');
        $this->assertCount(1, $result);
        $this->assertEquals('10:00:00', $result[0]->start_time);
    }

    public function test_has_overlap_true(): void
    {
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'space_id' => $this->space->uuid,
            'reserved_by' => $this->user->uuid,
            'status_id' => $this->statusConfirmada->uuid,
            'event_name' => 'Meeting 1',
            'event_date' => '2025-01-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'event_price' => 50,
        ]);

        $hasOverlap = ReservationRepository::hasOverlap($this->space->uuid, '2025-01-01', '10:30:00', '11:30:00');
        $this->assertTrue($hasOverlap);
    }

    public function test_has_overlap_false(): void
    {
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'space_id' => $this->space->uuid,
            'reserved_by' => $this->user->uuid,
            'status_id' => $this->statusConfirmada->uuid,
            'event_name' => 'Meeting 1',
            'event_date' => '2025-01-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'event_price' => 50,
        ]);

        $hasOverlap = ReservationRepository::hasOverlap($this->space->uuid, '2025-01-01', '11:00:00', '12:00:00');
        $this->assertFalse($hasOverlap);
    }

    public function test_has_overlap_false_cancelled(): void
    {
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'space_id' => $this->space->uuid,
            'reserved_by' => $this->user->uuid,
            'status_id' => $this->statusCancelada->uuid,
            'event_name' => 'Cancelled Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'event_price' => 50,
        ]);

        $hasOverlap = ReservationRepository::hasOverlap($this->space->uuid, '2025-01-01', '10:30:00', '11:30:00');
        $this->assertFalse($hasOverlap);
    }
}
