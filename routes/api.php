<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\admin\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {



    // ROTAS APENAS PARA USER COM NIVEL ADMINISTRADOR
    Route::post('/register-user', [UsersController::class, 'store']);
    Route::post('/update-user/{id}', [UsersController::class, 'update']);
    Route::delete('/delete-user/{id}', [UsersController::class, 'delete']);
    Route::get('/get-all-user', [UsersController::class, 'getAll']);
    Route::get('/get-user/{id}', [UsersController::class, 'getId']);
    Route::post('/update-level/{id}', [UsersController::class, 'updateLevel']);
    Route::post('/assign-category-user/{id}', [UsersController::class, 'assignCategoryUser']);
});