<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\admin\CreateUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {



    // ROTAS APENAS PARA USER COM NIVEL ADMINISTRADOR
    Route::post('/register-user', [CreateUsersController::class, 'store']);
    Route::post('/update-user/{id}', [CreateUsersController::class, 'update']);
    Route::delete('/delete-user/{id}', [CreateUsersController::class, 'delete']);
    Route::get('/get-all-user', [CreateUsersController::class, 'getAll']);
    Route::get('/get-user/{id}', [CreateUsersController::class, 'getId']);
    Route::post('/update-level/{id}', [CreateUsersController::class, 'updateLevel']);
    Route::post('/assign-category-user/{id}', [CreateUsersController::class, 'assignCategoryUser']);
});