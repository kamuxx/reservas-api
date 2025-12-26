<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function () {
    return response()->json(['message' => 'API is working: ' . app()->version()]);
});

Route::group(["prefix" => "auth"], function () {
    Route::post("/register", [RegisterController::class, "register"]);
    Route::post("/activate", [AuthController::class, "activate"]);
});
