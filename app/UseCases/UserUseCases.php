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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class UserUseCases
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenRepository $tokenRepository
    ) {}

    public function registerNewUser(array $data): User
    {

        $user =  $this->userRepository::createNewUser($data);
        $user->load('role', 'status');
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
        $oToken = $this->tokenRepository->getByToken($token);
        if (!$oToken)
            throw new NotFoundHttpException("No se encontro el token");

        if ($oToken->isExpired())
            throw new UnprocessableEntityHttpException("El token ha expirado");

        if ($oToken->isUsed() || $oToken->isValidated())
            throw new UnprocessableEntityHttpException("El token ya ha sido utilizado");

        if (!$oToken->isValidCode($activationCode))
            throw new UnprocessableEntityHttpException("El codigo de activacion no es valido");

        if ($oToken->user->isActive())
            throw new UnprocessableEntityHttpException("El usuario ya esta activo");

        try {
            $this->tokenRepository->update(UserActivationToken::class, ["uuid" => $oToken->uuid], ["validated_at" => Carbon::now(), "used_at" => Carbon::now()]);
            $this->userRepository::activateUser(["uuid" => $oToken->uuid]);
        } catch (NotFoundHttpException | UnprocessableEntityHttpException $e) {
            $exceptionMessage = "Error al activar la cuenta: " . $e->getMessage();
            if ($e instanceof NotFoundHttpException)
                throw new NotFoundHttpException($exceptionMessage);
            if ($e instanceof UnprocessableEntityHttpException)
                throw new UnprocessableEntityHttpException($exceptionMessage);
            if ($e instanceof UnauthenticatedException)
                throw new UnauthenticatedException($exceptionMessage);
        } catch (Throwable $e) {
            $exceptionMessage = "Error al activar la cuenta: " . $e->getMessage();
            throw new \Exception($exceptionMessage);
        }
    }

    public function login(array $credentials, ?string $ipAddress = null, ?string $userAgent = null): string
    {
        ["email" => $email, "password" => $password] = $credentials;
        $user = $this->userRepository->getOneBy(User::class, ["email" => $email]);

        try {
            if (!$user) {
                $this->logLoginAttempt(null, $email, $ipAddress, $userAgent, 'failed', 'Usuario no encontrado');
                throw new UnprocessableEntityHttpException("Las credenciales son incorrectas");
            }
            
            $user->load("role", "status");

            if (!$user->isActive()) {
                $this->logLoginAttempt($user->uuid, $email, $ipAddress, $userAgent, 'failed', 'Usuario no activo');
                throw new UnprocessableEntityHttpException("Las credenciales son incorrectas");
            }

            if (!$user->isValidPassword($password)) {
                $this->logLoginAttempt($user->uuid, $email, $ipAddress, $userAgent, 'failed', 'Contraseña inválida');
                throw new UnprocessableEntityHttpException("Las credenciales son incorrectas");
            }

            if (!$token = auth('api')->attempt($credentials)) {
                $this->logLoginAttempt($user->uuid, $email, $ipAddress, $userAgent, 'failed', 'Credenciales incorrectas');
                throw new UnprocessableEntityHttpException("Las credenciales son incorrectas");
            }

            $this->userRepository::updateLastLoginAt($user);
            $this->logLoginAttempt($user->uuid, $email, $ipAddress, $userAgent, 'success');

            return $token;
        } catch (Throwable $e) {
            if (!($e instanceof UnprocessableEntityHttpException)) {
                $this->logLoginAttempt($user ? $user->uuid : null, $email, $ipAddress, $userAgent, 'failed', $e->getMessage());
            }
            throw $e;
        }
    }

    private function logLoginAttempt(?string $userUuid, string $email, ?string $ipAddress, ?string $userAgent, string $status, ?string $reason = null): void
    {
        \Illuminate\Support\Facades\DB::table('login_audit_trails')->insert([
            'user_uuid' => $userUuid,
            'email_attempt' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => $status,
            'failure_reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function logout(): bool
    {
        try {
            auth('api')->logout();
            return true;
        } catch (Throwable $e) {
            $exceptionMessage = "Error al cerrar sesión: " . $e->getMessage();
            throw new \Exception($exceptionMessage);
        }
    }
}
