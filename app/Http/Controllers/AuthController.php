<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    /**
     * Logga in och skapa en användarsession
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('accessToken')->plainTextToken;

            // 🔹 Kontrollera om användaren redan har en aktiv session
            $session = DB::table('sessions')->where('user_id', $user->id)->first();

            if (!$session) {
                // 🔹 Skapa en ny session
                $session_id = (string) Uuid::uuid4();

                DB::table('sessions')->insert([
                    'id' => $session_id,
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'payload' => '',
                    'last_activity' => now()->timestamp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $session_id = $session->id;
            }

            // 🔹 Spara session_id i Laravel-sessionen
            Session::put('chat_session_id', $session_id);

            return response()->json([
                'accessToken' => $token,
                'session_id' => $session_id, // 🔹 Returnera session_id så att frontend kan använda det
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Login failed: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Logga ut användaren och ta bort sessionen
     */
    public function logout(Request $request)
    {
       $request->user()->tokens()->delete(); 

        return response()->json(['message' => 'Logged out successfully'], 200);

    }
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