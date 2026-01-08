<?php 

namespace Repositories;

use App\Models\UserActivationToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenRepository extends BaseRepository
{
    public static function getByToken(string $token): ?UserActivationToken
    {
        $tokenActivation = parent::getOneBy(UserActivationToken::class, ["token" => $token]);
        if(!$tokenActivation) return null;
        if(!$tokenActivation instanceof UserActivationToken) return null;
        return $tokenActivation;
    }
}