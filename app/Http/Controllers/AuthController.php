<?php

namespace App\Http\Controllers;
use app\Models\User;
use Exception;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request) {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:8',
            ]) 
            user::create([
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
