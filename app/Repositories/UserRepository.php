<?php 

namespace Repositories;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserRepository extends BaseRepository
{    
    /**
     * Inserta un nuevo usuario
     * @param array $data
     * @return User
     */
    public static function createNewUser(array $data): User
    {
        $user = self::insert(User::class, $data);
        if(!$user) throw new \Exception("Error al insertar el usuario");
        if(!$user instanceof User) throw new \Exception("Error al insertar el usuario");
        return $user;
    }

    public static function updateUser(array $filters, array $data): bool
    {
        return self::update(User::class, $filters, $data);
    }

    public static function activateUser(array $filters): bool
    {
        $status = Status::where('name', 'active')->first();
        if(!$status) throw new \Exception("Error al activar el usuario");
        return self::updateUser($filters, ["status_id" => $status->uuid]);
    }

    public static function updateLastLoginAt(Model $user): bool
    {
        return self::updateUser(["uuid" => $user->uuid], ["last_login_at" => Carbon::now()]);
    }
}