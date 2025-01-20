<?php

use App\Http\Controllers\Authentication\LoginController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\CategorysController;
use App\Http\Controllers\Admin\PDFBuyProductsOnAlertController;
use App\Http\Controllers\Authentication\LogoutController;
use App\Http\Controllers\ExitsController;
use App\Http\Controllers\InputsController;
use App\Http\Controllers\ProductAlertController;
use App\Http\Controllers\ProductEquipamentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\SystemLogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [LoginController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {

    //STORAGE_LOCATION
    Route::post('/storage-location', [StorageLocationController::class, 'storageLocation']);
    Route::post('/update-storage-location/{id}', [StorageLocationController::class, 'updateStorageLocation']);
    Route::get('/get-all-storage-location', [StorageLocationController::class, 'getAllStorageLocation']);
    Route::get('/get-storage-location/{id}', [StorageLocationController::class, 'getIdStorageLocation']);
    Route::post('/reverse-deleted-storage-location/{id}', [StorageLocationController::class, 'reverseDeletedStorageLocation']);

    //INPUTS
    Route::post('/inputs', [InputsController::class, 'store']);
    Route::post('/update-inputs/{id}', [InputsController::class, 'update']);
    Route::get('/get-all-inputs', [InputsController::class, 'getAllInputs']);
    Route::get('/get-inputs/{id}', [InputsController::class, 'getIdInputs']);
    Route::get('/get-all-inputs-in-alerts', [InputsController::class, 'inputsInAlerts']);
    Route::get('/get-inputs-ordered-expiration-date/{id}', [InputsController::class, 'getInputsWithOrderByExpirationDate']);

    //PRODUCT_ALERT
    Route::get('/product-alert', [ProductAlertController::class, 'getAllProductAlert']);

    //RESERVATION
    Route::post('/reservation', [ReservationController::class, 'reservation']);
    Route::post('/finished-reservation/{id}', [ReservationController::class, 'pendingReservationCompleted']);
    Route::post('/update-reservation/{id}', [ReservationController::class, 'updateReservation']);
    Route::get('/get-all-reservation', [ReservationController::class, 'getAllReservation']);
    Route::get('/get-reservation/{id}', [ReservationController::class, 'getIdReservation']);
    Route::get('/get-all-delayed-reservation', [ReservationController::class, 'delayedReservations']);

    //EXITS
    Route::post('/exits', [ExitsController::class, 'exits']);
    Route::post('/update-exits/{id}', [ExitsController::class, 'updateExits']);
    Route::get('/get-all-exits', [ExitsController::class, 'getAllExits']);
    Route::get('/get-exits/{id}', [ExitsController::class, 'getIdExits']);

    //PRODUCT/EQUIPAMENTS
    Route::post('/product-equipaments', [ProductEquipamentController::class, 'store']);
    Route::post('/update-product-equipaments/{id}', [ProductEquipamentController::class, 'update']);
    Route::get('/git', [ProductEquipamentController::class, 'getAllProductEquipament']);
    Route::get('/get-product-equipaments/{id}', [ProductEquipamentController::class, 'getIdProductEquipament']);
    Route::post('/reverse-product-equipaments-deleted/{id}', [ProductEquipamentController::class, 'reverseDeletedProduct']);

    //ME
    Route::post('/update-password', [UsersController::class, 'updatePassword']);
    Route::get('/my-profile', [UsersController::class, 'myProfile']);
    Route::post('/logout', [LogoutController::class, 'logout']);

    //GENERATE PDF
    Route::get('/pdf', [PDFBuyProductsOnAlertController::class, 'generatedPDFBuyProductOnAlert']);

    //-------------------------------------------
    // ROUTES FOR ADMINISTRATOR LEVEL USERS ONLY 
    //-------------------------------------------

    //ALL LOGS
    Route::get('/get-all-logs', [SystemLogsController::class, 'getAllLogs']);

    //REVERSE FINISHED RESERVATION
    Route::post('/reverse-finalized-reservention/{id}', [ReservationController::class, 'reverseReservationCompleted']);

    //ALL DELETE
    Route::delete('/delete-exits/{id}', [ExitsController::class, 'delete']);
    Route::delete('/delete-product-equipaments/{id}', [ProductEquipamentController::class, 'delete']);
    Route::delete('/delete-reservation/{id}', [ReservationController::class, 'delete']);
    Route::delete('/delete-inputs/{id}', [InputsController::class, 'delete']);
    Route::delete('/delete-user/{id}', [UsersController::class, 'delete']);
    Route::delete('/delete-category/{id}', [CategorysController::class, 'delete']);
    Route::delete('/delete-storage-location/{id}', [StorageLocationController::class, 'delete']);

    //USERS
    Route::post('/register-user', [UsersController::class, 'store']);
    Route::post('/update-user/{id}', [UsersController::class, 'update']);
    Route::post('/update-level/{id}', [UsersController::class, 'updateLevel']);
    Route::post('/access-reservation/{id}', [UsersController::class, 'updateReservationEnable']);
    Route::post('/assign-category-user/{id}', [UsersController::class, 'assignCategoryUser']);
    Route::post('/reset-password/{id}', [UsersController::class, 'updatePasswordAdmin']);
    Route::get('/get-all-user', [UsersController::class, 'getAll']);
    Route::get('/get-user/{id}', [UsersController::class, 'getId']);
    Route::get('/view-category-user/{id}', [UsersController::class, 'viewCategoryUser']);
    Route::post('/reverse-deleted-user/{id}', [UsersController::class, 'reverseDeletedUser']);

    //CATEGORYS
    Route::post('/category', [CategorysController::class, 'store']);
    Route::post('/update-category/{id}', [CategorysController::class, 'update']);
    Route::get('/get-all-category', [CategorysController::class, 'getAllCategorys']);
    Route::get('/get-category/{id}', [CategorysController::class, 'getId']);
    Route::post('/reverse-deleted-category/{id}', [CategorysController::class, 'reverseDeletedCategory']);
});