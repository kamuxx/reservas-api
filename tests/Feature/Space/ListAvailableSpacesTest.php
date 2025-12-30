<?php

namespace Tests\Feature\Space;

use Tests\TestCase;
use App\Models\Space;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\PricingRule;
use App\Models\User;
use App\Models\Role;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ListAvailableSpacesTest extends TestCase
{
    use RefreshDatabase;

    private $spaceType;
    private $statusActive;
    private $pricingRule;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos básicos
        $roleAdmin = Role::firstOrCreate(['name' => 'admin'], ['uuid' => (string) Str::uuid()]);
        $this->statusActive = Status::firstOrCreate(['name' => 'active'], ['uuid' => (string) Str::uuid()]);
        Status::firstOrCreate(['name' => 'confirmada'], ['uuid' => (string) Str::uuid()]);
        Status::firstOrCreate(['name' => 'cancelada'], ['uuid' => (string) Str::uuid()]);

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->uuid,
            'status_id' => $this->statusActive->uuid,
        ]);

        $this->spaceType = SpaceType::firstOrCreate(['name' => 'Sala de Juntas'], ['uuid' => (string) Str::uuid()]);

        $this->pricingRule = PricingRule::firstOrCreate(['name' => 'Estándar'], [
            'uuid' => (string) Str::uuid(),
            'price_adjustment' => 0,
            'adjustment_type' => 'fixed',
        ]);
    }

    public function test_can_list_available_spaces(): void
    {
        // Crear 2 espacios activos
        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio 1',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio 2',
            'capacity' => 20,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        // Espacio inactivo (no debe aparecer)
        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Inactivo',
            'capacity' => 5,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => false,
            'created_by' => $this->admin->uuid,
        ]);

        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_fails_without_fecha_deseada(): void
    {
        $response = $this->getJson('/api/spaces/available');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_deseada']);
    }

    public function test_filters_by_space_type(): void
    {
        $otherType = SpaceType::firstOrCreate(['name' => 'Auditorio'], ['uuid' => (string) Str::uuid()]);

        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Tipo 1',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Tipo 2',
            'capacity' => 20,
            'spaces_type_id' => $otherType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . now()->format('Y-m-d') . '&space_type_id=' . $this->spaceType->uuid);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Espacio Tipo 1');
    }

    public function test_excludes_fully_occupied_spaces(): void
    {
        $date = now()->addDay()->format('Y-m-d');
        
        $space = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Ocupado',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        // Crear una reserva de 24 horas
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->admin->uuid,
            'space_id' => $space->uuid,
            'status_id' => Status::where('name', 'confirmada')->first()->uuid,
            'event_name' => 'Evento 24h',
            'event_date' => $date,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59', // Prácticamente 24h, strftime %s dará 86399 o similar.
            'event_price' => 100,
            'pricing_rule_id' => $this->pricingRule->uuid,
        ]);

        // Para asegurar que llega a 86400 o que la lógica sea robusta:
        // En SQLite strftime("%s", "23:59:59") - strftime("%s", "00:00:00") es 86399.
        // Vamos a usar 00:00:00 y 24:00:00 si es posible, o ajustar el test.
        
        // Ajustamos la reserva para que sume exactamente 86400 si es posible, o bajamos el umbral a 86300.
        // Pero HU dice "completamente reservado".
        
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . $date);

        // Si la reserva es de 23:59:59, todavía tiene 1 segundo libre.
        // Vamos a crear dos reservas que sumen 24h.
        
        Reservation::where('event_name', 'Evento 24h')->delete();
        
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->admin->uuid,
            'space_id' => $space->uuid,
            'status_id' => Status::where('name', 'confirmada')->first()->uuid,
            'event_name' => 'Mañana',
            'event_date' => $date,
            'start_time' => '00:00:00',
            'end_time' => '12:00:00',
            'event_price' => 50,
            'pricing_rule_id' => $this->pricingRule->uuid,
        ]);
        
        Reservation::create([
            'uuid' => (string) Str::uuid(),
            'reserved_by' => $this->admin->uuid,
            'space_id' => $space->uuid,
            'status_id' => Status::where('name', 'confirmada')->first()->uuid,
            'event_name' => 'Tarde',
            'event_date' => $date,
            'start_time' => '12:00:00',
            'end_time' => '24:00:00', // Algunos DB aceptan 24:00:00, otros no.
            'event_price' => 50,
            'pricing_rule_id' => $this->pricingRule->uuid,
        ]);

        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . $date);

        // Debería estar vacío si el espacio está 100% ocupado
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
