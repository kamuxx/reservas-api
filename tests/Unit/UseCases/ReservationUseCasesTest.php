<?php

namespace Tests\Unit\UseCases;

use Tests\TestCase;
use UseCases\ReservationUseCases;
use Repositories\ReservationRepository;
use Repositories\SpaceRepository;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\Status;
use App\Models\PricingRule;
use App\Models\SpaceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;

class ReservationUseCasesTest extends TestCase
{
    use RefreshDatabase;

    private $reservationRepository;
    private $spaceRepository;
    private $reservationUseCases;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reservationRepository = Mockery::mock(ReservationRepository::class);
        $this->spaceRepository = Mockery::mock(SpaceRepository::class);
        $this->reservationUseCases = new ReservationUseCases($this->reservationRepository, $this->spaceRepository);
        
        // Setup essential statuses and roles for UseCase logic and factories
        Status::create(['name' => 'confirmada', 'uuid' => 'status-confirmada']);
        Status::create(['name' => 'cancelada', 'uuid' => 'status-cancelada']);
        Status::create(['name' => 'active', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        \App\Models\Role::create(['name' => 'user', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        
        // Add SpaceType and PricingRule for factories
        SpaceType::create(['name' => 'Default Room', 'uuid' => (string) \Illuminate\Support\Str::uuid()]);
        PricingRule::create([
            'name' => 'Default Rule',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_reservation_space_not_found(): void
    {
        $data = ['space_id' => 'invalid-uuid'];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("El espacio no existe.");
        $this->expectExceptionCode(404);

        $this->reservationUseCases->create($data, 'user-uuid');
    }

    public function test_cancel_reservation_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La reserva no existe.");

        $this->reservationUseCases->cancel('invalid-uuid', 'user-uuid', 'user');
    }

    public function test_create_reservation_success(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Hourly',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 50,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom',
        ]);
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space A',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada', // Reuse existing setup
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')->once()->andReturn(new Reservation([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100.0,
            'pricing_rule_id' => $pricingRule->uuid,
        ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);

        $this->assertInstanceOf(Reservation::class, $result);
        $this->assertEquals(100.0, $result->event_price);
        $this->assertDatabaseHas('entity_audit_trails', [
            'entity_name' => 'reservation',
            'operation' => 'create',
            'user_uuid' => $user->uuid
        ]);
    }

    public function test_create_reservation_overlap_error(): void
    {
        $user = \App\Models\User::factory()->create();
        $space = Space::factory()->create();

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("El espacio ya se encuentra reservado en el horario seleccionado.");
        $this->expectExceptionCode(409);

        $this->reservationUseCases->create($data, $user->uuid);
    }

    public function test_cancel_reservation_success(): void
    {
        $user = \App\Models\User::factory()->create();
        $space = Space::factory()->create();
        $pricingRule = PricingRule::create([
            'name' => 'Rule 2',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);
        
        $reservation = Reservation::create([
            'uuid' => 'res-cancel-uuid',
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $result = $this->reservationUseCases->cancel('res-cancel-uuid', $user->uuid, 'user');

        $this->assertInstanceOf(Reservation::class, $result);
        $this->assertEquals('status-cancelada', $result->status_id);
        $this->assertEquals($user->uuid, $result->cancellation_by);
    }

    public function test_cancel_reservation_unauthorized(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();
        $space = Space::factory()->create();
        $pricingRule = PricingRule::create([
            'name' => 'Rule 3',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);

        $reservation = Reservation::create([
            'uuid' => 'res-unauth-uuid',
            'reserved_by' => $otherUser->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No tiene permisos para cancelar esta reserva.");
        $this->expectExceptionCode(403);

        $this->reservationUseCases->cancel('res-unauth-uuid', $user->uuid, 'user');
    }

    public function test_create_reservation_no_pricing_rule(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala 2', 'uuid' => (string) Str::uuid()]);
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space No Price',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada',
            'pricing_rule_id' => null,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')->once()->andReturn(new Reservation([
            'uuid' => (string) Str::uuid(),
            'event_price' => 0.0,
        ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);
        $this->assertEquals(0.0, $result->event_price);
    }

    public function test_cancel_reservation_as_admin(): void
    {
        $admin = \App\Models\User::factory()->create();
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala 4', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Rule 5',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space for Admin Cancel',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada',
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid,
        ]);

        $reservation = Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $result = $this->reservationUseCases->cancel($reservation->uuid, $admin->uuid, 'admin');

        $this->assertEquals('status-cancelada', $result->status_id);
    }

    public function test_calculate_price_exception(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala 3', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Bad Rule',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
        ]);
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space Bad Time',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada',
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => 'invalid',
            'end_time' => 'time',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')->once()->andReturn(new Reservation([
            'uuid' => (string) Str::uuid(),
            'event_price' => 0.0,
        ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);
        $this->assertEquals(0.0, $result->event_price);
    }

    public function test_create_reservation_fallback_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala Fallback', 'uuid' => (string) Str::uuid()]);
        
        // Rename confirmada status to trigger fallback instead of deleting (to avoid FK issues)
        Status::where('name', 'confirmada')->update(['name' => 'confirmada_disabled']);
        Status::create(['name' => 'agendada', 'uuid' => 'status-agendada']);

        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space Fallback Status',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-agendada',
            'pricing_rule_id' => null,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['status_id'] === 'status-agendada';
            }))
            ->andReturn(new Reservation([
                'uuid' => (string) Str::uuid(),
                'status_id' => 'status-agendada',
            ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);
        $this->assertEquals('status-agendada', $result->status_id);
    }

    public function test_cancel_reservation_status_not_configured(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::where('name', 'Default Room')->first();
        $pricingRule = PricingRule::where('name', 'Default Rule')->first();
        
        // Create space manually to avoid factory random status which might be 'cancelada'
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space for Cancel Test',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada',
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid,
        ]);

        $reservation = Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        // Rename cancelada statuses to trigger 500 error instead of deleting
        Status::whereIn('name', ['cancelada', 'canceled'])->update(['name' => 'none']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Estado 'cancelada' no configurado en el sistema.");
        $this->expectExceptionCode(500);

        $this->reservationUseCases->cancel($reservation->uuid, $user->uuid, 'user');
    }

    public function test_calculate_price_not_fixed(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala Not Fixed', 'uuid' => (string) Str::uuid()]);
        $pricingRule = PricingRule::create([
            'name' => 'Percent Rule',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'percentage', // Not 'fixed'
        ]);
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space Percent',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'status-confirmada',
            'pricing_rule_id' => $pricingRule->uuid,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')->once()->andReturn(new Reservation([
            'uuid' => (string) Str::uuid(),
            'event_price' => 0.0,
        ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);
        $this->assertEquals(0.0, $result->event_price);
    }

    public function test_create_reservation_last_status_fallback(): void
    {
        $user = \App\Models\User::factory()->create();
        $spaceType = SpaceType::create(['name' => 'Sala Last Fallback', 'uuid' => (string) Str::uuid()]);
        
        // Rename all potential statuses to trigger Status::first() fallback
        Status::whereIn('name', ['confirmada', 'agendada', 'active'])->update(['name' => 'disabled']);
        $fallbackStatus = Status::create(['name' => 'Fallback', 'uuid' => 'fallback-uuid']);

        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Space Last Fallback',
            'capacity' => 10,
            'spaces_type_id' => $spaceType->uuid,
            'status_id' => 'fallback-uuid',
            'pricing_rule_id' => null,
            'created_by' => $user->uuid,
        ]);

        $data = [
            'space_id' => $space->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->reservationRepository->shouldReceive('hasOverlap')->andReturn(false);
        $this->reservationRepository->shouldReceive('create')->once()->andReturn(new Reservation([
            'uuid' => (string) Str::uuid(),
            'status_id' => 'fallback-uuid',
        ]));

        $result = $this->reservationUseCases->create($data, $user->uuid);
        $this->assertEquals('fallback-uuid', $result->status_id);
    }

    public function test_cancel_reservation_invalid_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $space = Space::factory()->create();
        $pricingRule = PricingRule::create([
            'name' => 'Rule 4',
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 10,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom'
        ]);
        
        $statusFinalizada = Status::create(['name' => 'finalizada', 'uuid' => 'status-finalizada']);

        $reservation = Reservation::create([
            'uuid' => 'res-invalid-status',
            'reserved_by' => $user->uuid,
            'space_id' => $space->uuid,
            'status_id' => $statusFinalizada->uuid,
            'event_name' => 'Meeting',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'event_price' => 100,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Solo se pueden cancelar reservas en estado confirmada.");
        $this->expectExceptionCode(422);

        $this->reservationUseCases->cancel('res-invalid-status', $user->uuid, 'user');
    }
}
