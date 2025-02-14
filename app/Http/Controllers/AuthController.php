<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function login(Request $request) {
        try {
        $request->validate([
            'email' => 'required|email',
                'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('accessToken');
        return response()->json(['accessToken' => $token->plainTextToken],200);
        } catch(\Exception $e) {
return response()->json(['message' => $e->getMessage()], 401);
        }
    }
public function logout(Request $request) {
    $request->user()->tokens()->delete();

    return response()->json(['message' => 'Logged out'], 200);
}
// 6|rfGiV4XuASqEk0H8gniXR23YZhJGyCdzqjy2ja8M16322dde
    public function register(Request $request) {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:8',
            ]);
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
return response()->json(['message' => 'New user registered'], 201);
        } catch(\Exception $e) {
            return response()->json(['message' => 'Registration failed'], 400);
        }
    }
}
