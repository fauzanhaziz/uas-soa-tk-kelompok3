<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('login', [AuthController::class, 'login']);

// Contoh rute yang dilindungi:
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('me', [AuthController::class, 'me']); 
    // Anda perlu menambahkan method 'me' di AuthController
});
