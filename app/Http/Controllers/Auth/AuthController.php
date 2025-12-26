<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UseCases\UserUseCases;
use App\Http\Requests\Auth\ValidateAccountRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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
}
