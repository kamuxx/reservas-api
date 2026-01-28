<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UseCases\UserUseCases;
use App\Http\Requests\Auth\ValidateAccountRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthenticatedException;
use App\Http\Requests\Auth\ChangePasswordRequest;

class AuthController extends Controller
{
    public function __construct(private UserUseCases $userUseCases){}

    public function activate(ValidateAccountRequest $request){
        try {
            $activationCode = $request->activation_code;
            $token = $request->token;            
            
            $this->userUseCases->activateAccount($token, (int)$activationCode);
            return $this->success(200,"Cuenta activada exitosamente");
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $message = "Error al activar la cuenta: " . $e->getMessage();     
            return $this->clientError($e, $message);
        }
        catch (\Throwable $th) {
            $message = "Error al activar la cuenta: " . $th->getMessage();     
            return $this->serverError($th, $message);
        }
    }

    public function login(LoginRequest $request){
        try {
            $credentials = $request->only("email", "password");
            $token = $this->userUseCases->login(
                $credentials,
                $request->ip(),
                $request->userAgent()
            );
            return $this->respondWithToken($token);
        } 
        catch (AuthenticationException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $message = "Error al iniciar sesión: " . $e->getMessage();     
            return $this->clientError($e, $message);
        }
        catch (\Throwable $th) {
            $message = "Error al iniciar sesión: " . $th->getMessage();     
            return $this->serverError($th, $message);
        }
    }

    protected function respondWithToken(string $token)
    {
        return $this->success(200,"Login exitoso", [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    public function logout()
    {
        try {
            $this->userUseCases->logout();
            return $this->success(204);   
        } catch (\Throwable $th) {
            $message = "Error al cerrar sesión: " . $th->getMessage();     
            return $this->serverError($th, $message);
        }
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = auth('api')->user();
            
            $this->userUseCases->changePassword(
                $user,
                $request->current_password,
                $request->new_password
            );
            
            return $this->success(200, "Contraseña actualizada exitosamente");
        } catch (UnprocessableEntityHttpException $e) {
            return $this->clientError($e, $e->getMessage());
        } catch (\Throwable $th) {
            return $this->serverError($th, "Error al actualizar la contraseña");
        }
    }
}
