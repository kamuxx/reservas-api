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

    public function update(Space $space, array $data): void
    {
        DB::transaction(function () use ($space, $data) {
            $user = auth('api')->user();
            $data['updated_by'] = $user->uuid;
            // 1. Actualizar el espacio
            $spaceUpdated = $this->spaceRepository::updateSpace(['uuid' => $space->uuid],$data);

            // Validación de actualización (Consistente con el patrón del proyecto)
            if (!$spaceUpdated || !$spaceUpdated instanceof Space) {
                throw new \Exception("Error al actualizar el espacio");
            }

            // 2. Registrar la auditoría (HU-005)
            DB::table('entity_audit_trails')->insert([
                'entity_name' => 'spaces',
                'entity_id'   => $space->uuid,
                'operation'   => 'update',
                'before_state' => json_encode($space->toArray()),
                'after_state' => json_encode($spaceUpdated->toArray()),
                'user_uuid'   => $data['updated_by'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });
    }

    public function list(array $filters, bool $isAdmin = false)
    {
        if (!$isAdmin) {
            $filters['is_active'] = true;
        }

        $perPage = $filters['per_page'] ?? 15;
        return $this->spaceRepository::paginate($filters, $perPage);
    }

    public function find(string $id, bool $isAdmin = false): ?Space
    {
        $space = $this->spaceRepository::findByUuid($id);

        if (!$space) {
            return null;
        }

        if (!$isAdmin && !$space->is_active) {
            return null;
        }

        return $space;
    }
}
