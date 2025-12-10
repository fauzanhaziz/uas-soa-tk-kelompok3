<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TabunganController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route untuk melihat semua data
Route::get('/tabungan', [TabunganController::class, 'index']);

// Route untuk menambah transaksi (setor/tarik)
Route::post('/tabungan', [TabunganController::class, 'store']);

// Route untuk cek saldo siswa
Route::get('/saldo/{id_siswa}', [TabunganController::class, 'cekSaldo']);