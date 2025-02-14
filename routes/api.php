<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

// Route för autentiserade användare
Route::middleware('auth:sanctum')->post('/chat', [ChatbotController::class, 'chat']);

// Route för gästanvändare (utan autentisering)
//Route::post('/chat', [ChatbotController::class, 'chatWithoutToken']);

// Användarinfo (kräver autentisering)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth-routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');