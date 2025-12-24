<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use UseCases\UserUseCases;

class RegisterController extends Controller
{
    public function __construct(
        private UserUseCases $userUseCases
        ){}

    public function register(RegisterUserRequest $request){
        try {
            $user = $this->userUseCases->registerNewUser($request->all());
            return $this->success(201,"User was created successfully",$user->toArray());
        } catch (\PDOException $th) {
            return $this->serverError($th, "Error creating user");
        }
    }
}
