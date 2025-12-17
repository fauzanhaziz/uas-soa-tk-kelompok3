<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

/*
|--------------------------------------------------------------------------
| Gateway API Routes
|--------------------------------------------------------------------------
|
| Gateway ini menghubungkan User Service (JWT Auth) dan Tabungan Service
| Semua routes protected dengan middleware auth:api (JWT)
|
*/

Route::prefix('gateway')->middleware('auth:api')->group(function () {
    
    // Endpoint 1: Melihat tabungan & saldo user yang login
    Route::get('/my-tabungan', [GatewayController::class, 'getMyTabungan']);
    
    // Endpoint 2: Menambah transaksi tabungan (setor/tarik)
    Route::post('/tabungan', [GatewayController::class, 'storeTabungan']);
    
    // Endpoint 3: Cek saldo user tertentu (admin function)
    Route::get('/saldo/{user_id}', [GatewayController::class, 'checkUserSaldo']);
});