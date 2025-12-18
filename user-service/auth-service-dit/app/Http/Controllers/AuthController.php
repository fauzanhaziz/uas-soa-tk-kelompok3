<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // ← INI WAJIB
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('User Service: Login attempt', [
            'email' => $request->email
        ]);
        
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            Log::warning('User Service: Login failed - invalid credentials', [
                'email' => $request->email
            ]);
            
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Log::info('User Service: Login successful', [
            'email' => $request->email,
            'user_id' => Auth::guard('api')->id()
        ]);

        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        Log::info('User Service: Registration attempt', [
            'email' => $request->email,
            'name' => $request->name
        ]);
        
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // TANPA Hash::make
        ]);

        Log::info('User Service: Registration successful', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return response()->json([
            'message' => 'Register berhasil',
            'user' => $user
        ], 201);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
