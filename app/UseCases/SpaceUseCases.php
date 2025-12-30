<?php 

namespace UseCases;

use App\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Repositories\SpaceRepository;

class SpaceUseCases
{
    public function __construct(private SpaceRepository $spaceRepository){}

    /**
     * Registra un nuevo espacio y su auditoría en una transacción.
     * 
     * @param array $data
     * @return Space
     */
    public function register(array $data): Space
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear el espacio
            $space = $this->spaceRepository::create($data);

            // Validación de creación (Consistente con el patrón del proyecto)
            if (!$space || !$space instanceof Space) {
                throw new \Exception("Error al registrar el espacio");
            }

            // 2. Registrar la auditoría (HU-005)
            DB::table('entity_audit_trails')->insert([
                'entity_name' => 'spaces',
                'entity_id'   => $space->uuid,
                'operation'   => 'create',
                'after_state' => json_encode($space->toArray()),
                'user_uuid'   => $data['created_by'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $space;
        });
    }
}
