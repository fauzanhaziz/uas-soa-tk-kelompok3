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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api'); 
use App\Http\Controllers\UserController;

Route::middleware('auth:api')->get('/me', [UserController::class, 'me']);

Route::middleware('auth:api')->group(function () {

    // profile user login
    Route::get('/me', [UserController::class, 'me']);

    // user CRUD
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});