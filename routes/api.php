<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    // ROTAS APENAS PARA USER COM NIVEL ADMINISTRADOR
    //USERS
    Route::post('/register-user', [UsersController::class, 'store']);
    Route::post('/update-user/{id}', [UsersController::class, 'update']);
    Route::delete('/delete-user/{id}', [UsersController::class, 'delete']);
    Route::get('/get-all-user', [UsersController::class, 'getAll']);
    Route::get('/get-user/{id}', [UsersController::class, 'getId']);
    Route::post('/update-level/{id}', [UsersController::class, 'updateLevel']);
    Route::post('/assign-category-user/{id}', [UsersController::class, 'assignCategoryUser']);
    
    //CATEGORYS
    Route::get('/get-all-category', [CategoryController::class, 'getAllCategorys']);
    Route::get('/get-category/{id}', [CategoryController::class, 'getId']);
    Route::delete('/delete-category/{id}', [CategoryController::class, 'delete']);
    Route::post('/update-category/{id}', [CategoryController::class, 'update']);
    Route::post('/create-category/{id}', [CategoryController::class, 'store']);
});