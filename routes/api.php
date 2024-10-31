<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\CategorysController;
use App\Http\Controllers\ProductEquipamentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    //PRODUCT/EQUIPAMENTS
    Route::post('/update-product-equipaments/{id}', [ProductEquipamentController::class, 'update']);
    Route::post('/create-product-equipaments', [ProductEquipamentController::class, 'store']);


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
    Route::get('/get-all-category', [CategorysController::class, 'getAllCategorys']);
    Route::get('/get-category/{id}', [CategorysController::class, 'getId']);
    Route::delete('/delete-category/{id}', [CategorysController::class, 'delete']);
    Route::post('/update-category/{id}', [CategorysController::class, 'update']);
    Route::post('/create-category', [CategorysController::class, 'store']);
});