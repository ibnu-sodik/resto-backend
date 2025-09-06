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

    Route::apiResource('/orders', OrderController::class);
    Route::post('/orders/open', [OrderController::class, 'open']);
    Route::post('/orders/{id}/add-items', [OrderController::class, 'addFood']);
    Route::put('/orders/{orderId}/items/{itemId}', [OrderController::class, 'updateFood']);
    Route::delete('/orders/{orderId}/items/{itemId}', [OrderController::class, 'deleteFood']);
    Route::post('/orders/{id}/close', [OrderController::class, 'close']);
    Route::get('/orders/{id}/receipt', [OrderController::class, 'generateReceipt']);
});