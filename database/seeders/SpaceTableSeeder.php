<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\PricingRule;
use App\Models\Space;
use App\Models\SpaceFeature;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SpaceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncar tablas relacionadas primero
        DB::table('space_features')->truncate();
        DB::table('space_images')->truncate();
        DB::table('space_availability')->truncate();
        DB::table('spaces')->truncate();

        // Obtener datos base necesarios
        $spaceTypes = SpaceType::all();
        $statuses = Status::whereIn('name', ['active', 'inactive', 'maintenance'])->get();
        $activeStatus = Status::where('name', 'active')->first();
        $adminUser = User::where('email', 'admin@admin.com')->first();
        $pricingRules = PricingRule::where('is_active', true)->get();

        // Crear features/amenidades primero si no existen
        $featuresList = [
            'WiFi de Alta Velocidad',
            'Aire Acondicionado',
            'Calefacción',
            'Proyector',
            'Pantalla LED',
            'Sistema de Audio',
            'Pizarra Digital',
            'Pizarra Blanca',
            'Videoconferencia',
            'Sillas Ergonómicas',
            'Mesas Modulares',
            'Cocina/Cocineta',
            'Cafetera',
            'Refrigerador',
            'Microondas',
            'Baño Privado',
            'Acceso 24/7',
            'Seguridad',
            'Estacionamiento',
            'Recepción',
            'Limpieza Diaria',
            'Luz Natural',
            'Ventanas Amplias',
            'Balcón/Terraza',
            'Plantas Decorativas',
            'Zona de Descanso',
            'Almacenamiento',
            'Impresora',
            'Scanner',
            'Control de Iluminación',
            'Persianas/Cortinas',
            'Sistema Anti-Ruido',
            'Decoración Moderna',
            'Escritorio Ejecutivo',
            'Mesa de Reuniones',
            'Atril',
            'Micrófono',
            'Equipo de Grabación',
            'Cámara Profesional',
            'Iluminación Profesional'
        ];

        $features = [];
        foreach ($featuresList as $featureName) {
            $features[] = Feature::firstOrCreate(
                ['name' => $featureName],
                ['uuid' => Str::uuid()]
            );
        }

        // URLs de imágenes funcionales de ejemplo (Unsplash/Picsum)
        $imagesByType = [
            'Sala de Reuniones Ejecutiva' => [
                'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
                'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800',
                'https://images.unsplash.com/photo-1431540015161-0bf868a2d407?w=800',
            ],
            'Auditorio de Conferencias' => [
                'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',
                'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800',
                'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=800',
            ],
            'Oficina Privada Individual' => [
                'https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=800',
                'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800',
                'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=800',
            ],
            'Oficina Compartida (Coworking)' => [
                'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800',
                'https://images.unsplash.com/photo-1497366858526-0766cadbe8fa?w=800',
                'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800',
            ],
            'Sala de Entrevistas' => [
                'https://images.unsplash.com/photo-1556761175-4b46a572b786?w=800',
                'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=800',
                'https://images.unsplash.com/photo-1497366412874-3415097a27e7?w=800',
            ],
            'Estudio de Grabación' => [
                'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800',
                'https://images.unsplash.com/photo-1598653222000-6b7b7a552625?w=800',
                'https://images.unsplash.com/photo-1519508234439-4f23643125c1?w=800',
            ],
            'Sala de Capacitación' => [
                'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=800',
                'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800',
                'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=800',
            ],
            'Espacio para Eventos Sociales' => [
                'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=800',
                'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800',
                'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=800',
            ],
            'Terraza al Aire Libre' => [
                'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=800',
                'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?w=800',
                'https://images.unsplash.com/photo-1528605248644-14dd04022da1?w=800',
            ],
            'Laboratorio de Computación' => [
                'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800',
                'https://images.unsplash.com/photo-1560264280-88b68371db39?w=800',
                'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=800',
            ],
            'Sala de Juntas Pequeña' => [
                'https://images.unsplash.com/photo-1568992688065-536aad8a12f6?w=800',
                'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
                'https://images.unsplash.com/photo-1462826303086-329426d1aef5?w=800',
            ],
            'Sala de Telepresencia' => [
                'https://images.unsplash.com/photo-1573167243872-43c6433b9d40?w=800',
                'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?w=800',
                'https://images.unsplash.com/photo-1591994843349-f415893b3a6b?w=800',
            ],
        ];

        // Crear 100 espacios diversos
        $spaces = [];
        $count = 0;

        foreach ($spaceTypes as $type) {
            // 8-9 espacios por tipo
            $spacesPerType = ($count < 96) ? 8 : 9;

            for ($i = 1; $i <= $spacesPerType; $i++) {
                $count++;

                // Datos variables según el tipo
                $capacity = match ($type->name) {
                    'Sala de Reuniones Ejecutiva' => rand(6, 12),
                    'Auditorio de Conferencias' => rand(50, 200),
                    'Oficina Privada Individual' => rand(1, 2),
                    'Oficina Compartida (Coworking)' => rand(10, 30),
                    'Sala de Entrevistas' => rand(2, 4),
                    'Estudio de Grabación' => rand(3, 8),
                    'Sala de Capacitación' => rand(15, 40),
                    'Espacio para Eventos Sociales' => rand(30, 100),
                    'Terraza al Aire Libre' => rand(20, 60),
                    'Laboratorio de Computación' => rand(20, 35),
                    'Sala de Juntas Pequeña' => rand(4, 8),
                    'Sala de Telepresencia' => rand(4, 10),
                    default => rand(5, 20),
                };

                $descriptions = [
                    "Espacio moderno y luminoso, ideal para {$type->name}.",
                    "Ambiente profesional equipado con tecnología de última generación.",
                    "Espacio versátil y confortable con excelente ubicación.",
                    "Diseño contemporáneo que inspira creatividad y productividad.",
                    "Espacio premium con todas las comodidades necesarias.",
                    "Ambiente acogedor perfecto para reuniones efectivas.",
                    "Instalaciones de primera clase en el corazón de la ciudad.",
                    "Espacio innovador con vista panorámica excepcional.",
                ];

                // Seleccionar status (90% activo, 5% mantenimiento, 5% inactivo)
                $statusRand = rand(1, 100);
                $selectedStatus = $statusRand <= 90 ? $activeStatus : $statuses->random();

                $spaceUuid = Str::uuid();
                $space = Space::create([
                    'uuid' => $spaceUuid,
                    'name' => "{$type->name} - {$i}",
                    'description' => $descriptions[array_rand($descriptions)],
                    'capacity' => $capacity,
                    'spaces_type_id' => $type->uuid,
                    'status_id' => $selectedStatus->uuid,
                    'pricing_rule_id' => $pricingRules->random()->uuid ?? null,
                    'is_active' => $selectedStatus->name === 'active',
                    'created_by' => $adminUser->uuid,
                    'updated_by' => null,
                ]);

                $spaces[] = $space;

                // Agregar 3-5 imágenes por espacio
                $typeImages = $imagesByType[$type->name] ?? [
                    'https://picsum.photos/800/600?random=' . rand(1, 1000),
                    'https://picsum.photos/800/600?random=' . rand(1001, 2000),
                    'https://picsum.photos/800/600?random=' . rand(2001, 3000),
                ];

                $imageCount = rand(3, 5);
                for ($img = 0; $img < $imageCount; $img++) {
                    \DB::table('space_images')->insert([
                        'space_id' => $spaceUuid,
                        'image' => $typeImages[$img % count($typeImages)],
                        'is_main' => $img === 0 ? '1' : '0',
                    ]);
                }

                // Agregar 5-10 features aleatorias por espacio
                $featureCount = rand(5, 10);
                $selectedFeatures = collect($features)->random($featureCount);

                foreach ($selectedFeatures as $feature) {
                    DB::table('space_features')->insert([
                        'space_id' => $spaceUuid,
                        'feature_id' => $feature->uuid,
                    ]);
                }

                // Crear horarios de disponibilidad para los próximos 30 días (solo espacios activos)
                if ($selectedStatus->name === 'active') {
                    $startDate = Carbon::now();
                    $endDate = Carbon::now()->addDays(30);

                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                        // Definir horarios según el tipo de espacio
                        $schedules = match ($type->name) {
                            'Auditorio de Conferencias', 'Espacio para Eventos Sociales' => [
                                ['from' => '08:00', 'to' => '13:00'],
                                ['from' => '14:00', 'to' => '18:00'],
                                ['from' => '19:00', 'to' => '23:00'],
                            ],
                            'Oficina Compartida (Coworking)' => [
                                ['from' => '06:00', 'to' => '14:00'],
                                ['from' => '14:00', 'to' => '22:00'],
                            ],
                            'Terraza al Aire Libre' => [
                                ['from' => '10:00', 'to' => '15:00'],
                                ['from' => '16:00', 'to' => '21:00'],
                            ],
                            default => [
                                ['from' => '08:00', 'to' => '12:00'],
                                ['from' => '13:00', 'to' => '17:00'],
                                ['from' => '18:00', 'to' => '22:00'],
                            ],
                        };

                        foreach ($schedules as $schedule) {
                            // 80% disponible, 20% ya reservado
                            $isAvailable = rand(1, 100) <= 80;

                            \DB::table('space_availability')->insert([
                                'uuid' => \Str::uuid(),
                                'space_id' => $spaceUuid,
                                'available_date' => $date->format('Y-m-d'),
                                'available_from' => $schedule['from'],
                                'available_to' => $schedule['to'],
                                'is_available' => $isAvailable,
                                'max_capacity' => $capacity,
                                'slot_price' => rand(5000, 50000) / 100, // Precio entre $50 y $500
                                'created_by' => $adminUser->uuid,
                                'updated_by' => null,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                                'deleted_at' => null,
                            ]);
                        }
                    }
                }
            }
        }

        $this->command->info("✅ Creados {$count} espacios con todas sus relaciones:");
        $this->command->info("   - Imágenes: " . \DB::table('space_images')->count());
        $this->command->info("   - Features: " . \DB::table('space_features')->count());
        $this->command->info("   - Horarios disponibles: " . \DB::table('space_availability')->count());
    }
}
