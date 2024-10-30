<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\CreateUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    

    // ROTAS APENAS PARA ADMIN
    Route::post('/register-user', [CreateUserController::class, 'store']);
    Route::post('/update-user/{id}', [CreateUserController::class, 'update']);
    Route::post('/delete-user/{id}', [CreateUserController::class, 'delete']);
    Route::get('/get-all-user', [CreateUserController::class, 'getAll']);
    Route::get('/get-user/{id}', [CreateUserController::class, 'getId']);
    
    Route::post('/assign-category-user/{id}', [CreateUserController::class, 'assignCategoryUser']);
});