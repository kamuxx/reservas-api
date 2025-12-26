<?php

namespace UseCases;

use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Models\UserActivationToken;
use Illuminate\Support\Str;
use Repositories\UserRepository;
use App\Notifications\Auth\UserRegisteredNotificacion;
use Exception;
use Throwable;
use Carbon\Carbon;
use Repositories\TokenRepository;

class UserUseCases
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenRepository $tokenRepository
    ) {}

    public function registerNewUser(array $data): User
    {

        $user =  $this->userRepository::createNewUser($data);
        $user->load('role','status');
        if (!$user instanceof User) throw new \Exception("Error al insertar el usuario");
        try {
            $user->notify(new UserRegisteredNotificacion($user));
        } catch (Throwable $e) {
            $message = "Error al enviar notificación al usuario:    " . $e->getMessage();
            throw new \Exception($message);
        }
        return $user;
    }

    public function activateAccount(?string $token = null, ?int $activationCode = null): void
    {
        try {
            $oToken = $this->tokenRepository->getByToken($token);
            if (!$oToken)
                throw new \Exception("No se encontro el token");

            if ($oToken->isExpired())
                throw new \Exception("El token ha expirado");

            if ($oToken->isUsed())
                throw new \Exception("El token ya ha sido utilizado");

            if (!$oToken->isValidCode($activationCode))
                throw new \Exception("El codigo de activacion no es valido");

            $this->tokenRepository->update(UserActivationToken::class, ["uuid" => $oToken->uuid], ["validated_at" => Carbon::now(), "used_at" => Carbon::now()]);
            $this->userRepository::activateUser(["uuid" => $oToken->uuid]);
        } catch (Throwable $e) {
            $exceptionMessage = "Error al activar la cuenta:    " . $e->getMessage();
            throw new \Exception($exceptionMessage);
        }
    }
}
