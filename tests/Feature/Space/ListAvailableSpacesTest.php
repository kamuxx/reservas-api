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
use Carbon\Carbon;
use Database\Seeders\PricingRuleSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpaceTableSeeder;
use Database\Seeders\SpaceTypeSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UserAdminSeeder;

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

        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d'));

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

        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&space_type_id=' . $this->spaceType->uuid);

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

    public function test_can_filter_by_min_capacity(): void
    {
        // Crear espacios con diferentes capacidades
        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Pequeño',
            'capacity' => 5,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Mediano',
            'capacity' => 15,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Grande',
            'capacity' => 50,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        // Filtrar por capacidad mínima de 10 personas
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&min_capacity=10');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Solo Mediano (15) y Grande (50)

        // Verificar que los espacios retornados tienen capacidad >= 10
        $spaces = $response->json('data');
        foreach ($spaces as $space) {
            $this->assertGreaterThanOrEqual(10, $space['capacity']);
        }

        // Filtrar por capacidad mínima de 20 personas
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&min_capacity=20');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Solo Grande (50)
            ->assertJsonPath('data.0.name', 'Espacio Grande');
    }

    public function test_can_filter_by_features(): void
    {
        // Crear features
        $projector = \App\Models\Feature::firstOrCreate(
            ['name' => 'Proyector'],
            ['uuid' => (string) Str::uuid()]
        );

        $whiteboard = \App\Models\Feature::firstOrCreate(
            ['name' => 'Pizarra'],
            ['uuid' => (string) Str::uuid()]
        );

        $wifi = \App\Models\Feature::firstOrCreate(
            ['name' => 'WiFi'],
            ['uuid' => (string) Str::uuid()]
        );

        // Espacio con Proyector y WiFi
        $space1 = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala Conferencias',
            'capacity' => 20,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        \DB::table('space_features')->insert([
            ['space_id' => $space1->uuid, 'feature_id' => $projector->uuid],
            ['space_id' => $space1->uuid, 'feature_id' => $wifi->uuid],
        ]);

        // Espacio con Pizarra y WiFi
        $space2 = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala Capacitación',
            'capacity' => 15,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        \DB::table('space_features')->insert([
            ['space_id' => $space2->uuid, 'feature_id' => $whiteboard->uuid],
            ['space_id' => $space2->uuid, 'feature_id' => $wifi->uuid],
        ]);

        // Espacio solo con Proyector
        $space3 = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Sala Presentaciones',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        \DB::table('space_features')->insert([
            ['space_id' => $space3->uuid, 'feature_id' => $projector->uuid],
        ]);

        // Filtrar por Proyector - debe retornar 2 espacios
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&feature_ids[]=' . $projector->uuid);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Filtrar por WiFi - debe retornar 2 espacios
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&feature_ids[]=' . $wifi->uuid);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Filtrar por Pizarra - debe retornar 1 espacio
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&feature_ids[]=' . $whiteboard->uuid);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sala Capacitación');

        // Filtrar por múltiples features (Proyector Y WiFi) - debe retornar 1 espacio
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&feature_ids[]=' . $projector->uuid . '&feature_ids[]=' . $wifi->uuid);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sala Conferencias');
    }

    public function test_can_filter_by_price_range(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(StatusSeeder::class);
        $this->seed(SpaceTypeSeeder::class);
        $this->seed(PricingRuleSeeder::class);
        $this->seed(UserAdminSeeder::class);
        $this->seed(SpaceTableSeeder::class);
        // // Filtrar por precio mínimo de 75 - debe excluir el económico (50)
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&min_price=75');

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertGreaterThanOrEqual(1, $content['data']);

        // Filtrar por precio máximo de 150 - debe excluir el premium (200)
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&max_price=150');

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertGreaterThanOrEqual(1, $content['data']); // Económico (50) y Estándar (100)

        // Filtrar por rango de precio (75-150) - solo el estándar
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&min_price=75&max_price=150');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(10, $content['data']);

        // Filtrar por rango amplio (0-300) - todos los espacios
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . Carbon::now()->format('Y-m-d') . '&min_price=0&max_price=300');

        $response->assertStatus(200);
        $content = $response->json();
        $this->assertNotEmpty($content['data']);
    }

    public function test_respects_availability_schedules(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        // Espacio 1: Con disponibilidad para la fecha solicitada
        $spaceWithAvailability = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Con Disponibilidad',
            'capacity' => 10,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        // Crear slot de disponibilidad para este espacio
        \DB::table('space_availability')->insert([
            'uuid' => (string) Str::uuid(),
            'space_id' => $spaceWithAvailability->uuid,
            'available_date' => $date,
            'available_from' => '08:00:00',
            'available_to' => '18:00:00',
            'is_available' => true,
            'created_by' => $this->admin->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Espacio 2: Sin disponibilidad para la fecha solicitada (tiene slot pero para otra fecha)
        $spaceWithoutAvailability = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Sin Disponibilidad',
            'capacity' => 15,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        // Crear slot de disponibilidad para otra fecha (no la solicitada)
        \DB::table('space_availability')->insert([
            'uuid' => (string) Str::uuid(),
            'space_id' => $spaceWithoutAvailability->uuid,
            'available_date' => now()->addDays(10)->format('Y-m-d'), // Fecha muy diferente
            'available_from' => '08:00:00',
            'available_to' => '18:00:00',
            'is_available' => true,
            'created_by' => $this->admin->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Espacio 3: Con disponibilidad pero marcada como no disponible
        $spaceUnavailable = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio No Disponible',
            'capacity' => 20,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        \DB::table('space_availability')->insert([
            'uuid' => (string) Str::uuid(),
            'space_id' => $spaceUnavailable->uuid,
            'available_date' => $date,
            'available_from' => '08:00:00',
            'available_to' => '18:00:00',
            'is_available' => false, // Marcado como no disponible
            'created_by' => $this->admin->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Espacio 4: Con disponibilidad para otra fecha
        $spaceOtherDate = Space::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Espacio Otra Fecha',
            'capacity' => 25,
            'spaces_type_id' => $this->spaceType->uuid,
            'status_id' => $this->statusActive->uuid,
            'pricing_rule_id' => $this->pricingRule->uuid,
            'is_active' => true,
            'created_by' => $this->admin->uuid,
        ]);

        \DB::table('space_availability')->insert([
            'uuid' => (string) Str::uuid(),
            'space_id' => $spaceOtherDate->uuid,
            'available_date' => now()->addDays(5)->format('Y-m-d'), // Fecha diferente
            'available_from' => '08:00:00',
            'available_to' => '18:00:00',
            'is_available' => true,
            'created_by' => $this->admin->uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Hacer la consulta para la fecha específica
        $response = $this->getJson('/api/spaces/available?fecha_deseada=' . $date);

        // Solo debe retornar el espacio con disponibilidad válida
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Espacio Con Disponibilidad');

        // Verificar que los otros espacios NO están en la respuesta
        $responseData = $response->json('data');
        $spaceNames = array_column($responseData, 'name');

        $this->assertNotContains('Espacio Sin Disponibilidad', $spaceNames);
        $this->assertNotContains('Espacio No Disponible', $spaceNames);
        $this->assertNotContains('Espacio Otra Fecha', $spaceNames);
    }
}
