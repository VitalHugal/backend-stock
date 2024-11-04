<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\CategorysController;
use App\Http\Controllers\ExitsController;
use App\Http\Controllers\ProductEquipamentController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    //RESERVATION
    Route::post('/reservation/{id}', [ReservationController::class, 'reservation']);
    Route::get('/get-all-reservation', [ReservationController::class, 'getAllReservation']);
    Route::get('/get-reservation/{id}', [ReservationController::class, 'getIdReservation']);
    Route::post('/update-reservation/{id}', [ReservationController::class, 'updateReservation']);
    Route::delete('/delete-reservation/{id}', [ReservationController::class, 'delete']);

    //EXITS
    Route::post('/exits/{id}', [ExitsController::class, 'exits']);
    Route::get('/get-all-exits', [ExitsController::class, 'getAllExits']);
    Route::get('/get-exits/{id}', [ExitsController::class, 'getIdExits']);
    Route::post('/update-exits/{id}', [ExitsController::class, 'updateExits']);

    //PRODUCT/EQUIPAMENTS
    Route::post('/update-product-equipaments/{id}', [ProductEquipamentController::class, 'update']);
    Route::post('/create-product-equipaments', [ProductEquipamentController::class, 'store']);
    Route::get('/get-all-product-equipaments', [ProductEquipamentController::class, 'getAllProductEquipament']);
    Route::get('/get-product-equipaments/{id}', [ProductEquipamentController::class, 'getIdProductEquipament']);


    /////////////////////////////////////////////////
    // ROTAS APENAS PARA USER COM NIVEL ADMINISTRADOR
    /////////////////////////////////////////////////

    Route::delete('/delete-exits/{id}', [ExitsController::class, 'delete']); 
    Route::delete('/delete-product-equipaments/{id}', [ProductEquipamentController::class, 'delete']);

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