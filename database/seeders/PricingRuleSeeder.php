<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingRule;
use Illuminate\Support\Str;

class PricingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PricingRule::truncate();
        $rules = [
            [
                'name' => 'Estándar',
                'description' => 'Precio base sin ajustes',
                'rule_type' => 'custom',
                'price_adjustment' => 0.00,
                'adjustment_type' => 'fixed',
                'priority' => 0
            ],
            [
                'name' => 'Descuento Early Bird 30d',
                'description' => 'Descuento por reservar con 30 días de anticipación',
                'rule_type' => 'early_bird',
                'days_before_min' => 30,
                'price_adjustment' => -15.00,
                'adjustment_type' => 'percentage',
                'priority' => 10
            ],
            [
                'name' => 'Recargo Last Minute',
                'description' => 'Recargo por reservar el mismo día',
                'rule_type' => 'last_minute',
                'days_before_max' => 1,
                'price_adjustment' => 10.00,
                'adjustment_type' => 'percentage',
                'priority' => 10
            ],
            [
                'name' => 'Descuento Fin de Semana',
                'description' => 'Descuento para reservas en sábado o domingo',
                'rule_type' => 'day_of_week',
                'applicable_days' => ["6", "7"], // Sábado y Domingo
                'price_adjustment' => -20.00,
                'adjustment_type' => 'percentage',
                'priority' => 5
            ],
            [
                'name' => 'Temporada Alta Verano',
                'description' => 'Incremento durante temporada de verano',
                'rule_type' => 'seasonal',
                'valid_from' => date('Y') . '-06-01',
                'valid_until' => date('Y') . '-08-31',
                'price_adjustment' => 25.00,
                'adjustment_type' => 'percentage',
                'priority' => 8
            ],
            [
                'name' => 'Promo Lanzamiento',
                'description' => 'Descuento promocional de apertura',
                'rule_type' => 'custom',
                'price_adjustment' => -50.00,
                'adjustment_type' => 'fixed',
                'priority' => 20
            ],
            [
                'name' => 'Tarifa Corporativa',
                'description' => 'Tarifa especial para clientes corporativos',
                'rule_type' => 'custom',
                'price_adjustment' => -10.00,
                'adjustment_type' => 'percentage',
                'priority' => 2
            ],
            [
                'name' => 'Recargo Nocturno',
                'description' => 'Incremento para reservas después de las 18:00',
                'rule_type' => 'custom',
                'price_adjustment' => 15.00,
                'adjustment_type' => 'percentage',
                'priority' => 5
            ],
            [
                'name' => 'Descuento Larga Duración',
                'description' => 'Para reservas de más de 5 horas',
                'rule_type' => 'custom',
                'price_adjustment' => -5.00,
                'adjustment_type' => 'percentage',
                'priority' => 3
            ],
            [
                'name' => 'Premium Suite',
                'description' => 'Tarifa para suites de lujo',
                'rule_type' => 'custom',
                'price_adjustment' => 100.00,
                'adjustment_type' => 'fixed',
                'priority' => 15
            ],
        ];

        foreach ($rules as $rule) {
            $uuid = Str::uuid()->toString();
            $rule['uuid'] = $uuid;
            PricingRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
