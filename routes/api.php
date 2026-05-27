<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group.
|
*/

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Pet
    Route::get('/pet', [PetController::class, 'show']);
    Route::post('/pet', [PetController::class, 'store']);
    Route::delete('/pet', [PetController::class, 'destroy']);

    // Pet actions
    Route::post('/pet/feed', [PetController::class, 'feed']);
    Route::post('/pet/play', [PetController::class, 'play']);
    Route::post('/pet/clean', [PetController::class, 'clean']);
    Route::post('/pet/heal', [PetController::class, 'heal']);
    Route::post('/pet/sleep', [PetController::class, 'sleep']);
    Route::post('/pet/wake', [PetController::class, 'wake']);
    Route::post('/pet/discipline', [PetController::class, 'discipline']);

    // Pet stats/history
    Route::get('/pet/history', [PetController::class, 'history']);
});
