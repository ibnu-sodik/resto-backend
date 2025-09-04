<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FoodController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\TablesController;

Route::get('/tables', [TablesController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/foods', FoodController::class);

    Route::apiResource('/tables', TablesController::class)->except(['index']);
    Route::post('/tables/{id}/reservation', [TablesController::class, 'reservation']);

    Route::post('/orders/open', [OrderController::class, 'open']);
    Route::post('/orders/{id}/add-items', [OrderController::class, 'addFood']);
});
