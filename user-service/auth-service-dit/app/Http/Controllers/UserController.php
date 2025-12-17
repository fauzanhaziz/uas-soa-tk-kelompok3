<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // public function me()
    // {
    //     return response()->json([
    //         'user' => Auth::guard('api')->user()
    //     ]);
    // }
    // =====================
    // PROFILE LOGIN
    // =====================
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    // =====================
    // GET ALL USERS
    // =====================
    public function index()
    {
        return response()->json(User::all());
    }

    // =====================
    // GET USER BY ID
    // =====================
    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    // =====================
    // CREATE USER
    // =====================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password // auto hash (casts)
        ]);

        return response()->json($user, 201);
    }

    // =====================
    // UPDATE USER
    // =====================
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6'
        ]);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json($user);
    }

    // =====================
    // DELETE USER
    // =====================
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json([
            'message' => 'User deleted'
        ]);
    }
}
