<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpaceType;
use Illuminate\Support\Str;

class SpaceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Sala de Reuniones Ejecutiva',
            'Auditorio de Conferencias',
            'Oficina Privada Individual',
            'Oficina Compartida (Coworking)',
            'Sala de Entrevistas',
            'Estudio de Grabación',
            'Sala de Capacitación',
            'Espacio para Eventos Sociales',
            'Terraza al Aire Libre',
            'Laboratorio de Computación',
            'Sala de Juntas Pequeña',
            'Sala de Telepresencia'
        ];

        foreach ($types as $type) {
            $uuid = Str::uuid()->toString();
            SpaceType::firstOrCreate(['name' => $type, 'uuid' => $uuid]);
        }
    }
}
