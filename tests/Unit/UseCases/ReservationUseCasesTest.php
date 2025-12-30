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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_reservation_space_not_found(): void
    {
        $data = ['space_id' => 'invalid-uuid'];
        
        // Since it's in a transaction, we need to handle the DB::transaction call if we were purely unit testing,
        // but here we are using RefreshDatabase and a real DB, which is easier for transactions.
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("El espacio no existe.");

        $this->reservationUseCases->create($data, 'user-uuid');
    }

    public function test_cancel_reservation_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La reserva no existe.");

        $this->reservationUseCases->cancel('invalid-uuid', 'user-uuid', 'user');
    }

    public function test_cancel_reservation_unauthorized(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();
        $space = \App\Models\Space::factory()->create();
        $pricingRule = PricingRule::create([
            'name' => 'Default',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'price_adjustment' => 0,
            'adjustment_type' => 'fixed',
            'rule_type' => 'custom',
            'is_active' => true,
        ]);
        
        $reservation = Reservation::create([
            'uuid' => 'res-uuid',
            'reserved_by' => $otherUser->uuid,
            'space_id' => $space->uuid,
            'status_id' => 'status-confirmada',
            'event_name' => 'Test',
            'event_date' => '2025-01-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'event_price' => 10,
            'pricing_rule_id' => $pricingRule->uuid,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No tiene permisos para cancelar esta reserva.");

        $this->reservationUseCases->cancel('res-uuid', $user->uuid, 'user');
    }
}
