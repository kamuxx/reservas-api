<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UseCases\UserUseCases;
use App\Http\Requests\Auth\ValidateAccountRequest;

class AuthController extends Controller
{
    public function __construct(private UserUseCases $userUseCases){}

    public function activate(ValidateAccountRequest $request){
        try {
            $activationCode = $request->activation_code;
            $token = $request->token;
            
            $this->userUseCases->activateAccount($token, (int)$activationCode);
            return $this->success(200,"Cuenta activada exitosamente");
        } catch (\Throwable $th) {
            $message = "Error al activar la cuenta:    " . $th->getMessage();     
            return $this->serverError($th, $message);
        }
    }
}
