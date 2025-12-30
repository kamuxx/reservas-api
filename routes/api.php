<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/docs', function () {
    return view('swagger');
});

Route::get('/', function () {
    return response()->json(['message' => 'API is working: ' . app()->version()]);
});

Route::group(["prefix" => "auth"], function () {
    Route::post("/register", [RegisterController::class, "register"]);
    Route::post("/activate", [AuthController::class, "activate"]);

    Route::group(["middleware" => "api"], function () {
        Route::post("/login", [AuthController::class, "login"])->name("login");
    });

    Route::group(["middleware" => "auth:api"], function () {
        Route::post("/logout", [AuthController::class, "logout"])->name("logout");
    });
});

Route::group(["prefix" => "spaces"], function () {
    Route::get("", [SpaceController::class, "index"])->name("spaces.index");
    Route::get("/{id}", [SpaceController::class, "show"])->name("spaces.show");

    Route::group(["middleware" => ["auth:api", "isAdmin"]], function () {
        Route::post("", [SpaceController::class, "store"])->name("spaces.store");
        Route::put("/{space}", [SpaceController::class, "update"])->name("spaces.update");
        Route::delete("/{id}", [SpaceController::class, "destroy"])->name("spaces.destroy");
    });
});
