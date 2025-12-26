<?php 

namespace Repositories;

use App\Models\UserActivationToken;

class TokenRepository extends BaseRepository
{
    public static function getByToken(string $token): ?UserActivationToken
    {
        $tokenActivation = parent::getBy(UserActivationToken::class, ["token" => $token]);
        if(!$tokenActivation) throw new \Exception("Token no encontrado");
        if(!$tokenActivation instanceof UserActivationToken) throw new \Exception("Token no encontrado");
        return $tokenActivation;
    }
}