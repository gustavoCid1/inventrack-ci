<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\ProductoApiController;
use App\Http\Controllers\Api\VentaApiController;
use Illuminate\Support\Facades\Route;

Route::post('/tokens', [AuthTokenController::class, 'store'])->name('api.tokens');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/productos', [ProductoApiController::class, 'index'])->name('api.productos.index');
    Route::get('/productos/{producto}', [ProductoApiController::class, 'show'])->name('api.productos.show');
    Route::post('/productos', [ProductoApiController::class, 'store'])->middleware('admin')->name('api.productos.store');
    Route::post('/ventas', [VentaApiController::class, 'store'])->name('api.ventas.store');
});
