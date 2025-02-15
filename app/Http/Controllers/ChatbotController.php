<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\ChatHistory;
use App\Models\Session as ChatSession;
use Ramsey\Uuid\Uuid;

class ChatbotController extends Controller
{
  public function chat(Request $request)
{
    $user = $request->user();

    if ($request->has('session_id')) {
        $session_id = $request->session_id;
    } else {
        $session_id = Session::get('chat_session_id');
    }

    if (!$session_id) {
        return response()->json(['error' => 'Session not found. Please log in again.'], 401);
    }

    // 🔹 Kontrollera om sessionen existerar
    $sessionExists = DB::table('sessions')->where('id', $session_id)->exists();

    if (!$sessionExists) {
        return response()->json(['error' => 'Invalid session. Please log in again.'], 401);
    }

    // 🔹 Hämta tidigare meddelanden för sessionen
    $previousMessages = ChatHistory::where('user_id', $user->id)
        ->where('session_id', $session_id)
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(fn($chat) => [
            ['role' => 'user', 'content' => $chat->user_message],
            ['role' => 'assistant', 'content' => $chat->bot_response],
        ])
        ->flatten(1)
        ->toArray();

    $messages = array_merge($previousMessages, [
        ['role' => 'user', 'content' => $request->message]
    ]);

    // 🔹 Skicka meddelandet till AI-modellen
    $response = Http::post('http://localhost:11434/api/chat', [
        'model' => 'mistral',
        'messages' => $messages,
        'stream' => false,
    ]);

    $bot_response = $response->json()['message'] ?? 'No response received';
    $bot_response = is_array($bot_response) ? json_encode($bot_response) : $bot_response;


    // 🔹 Spara chatten i databasen
    ChatHistory::create([
        'user_id' => $user->id,
        'session_id' => $session_id,
        'user_message' => $request->message,
        'bot_response' => $bot_response,
    ]);

    return response()->json([
        'session_id' => $session_id,
        'response' => $bot_response
    ]);
}

public function chatWithoutToken(Request $request)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    \Log::info('Guest chat request received', ['message' => $request->message]);

    // 🔹 Skicka meddelandet till AI-modellen utan att spara historik
    $response = Http::post('http://localhost:11434/api/chat', [
        'model' => 'mistral',
        'messages' => [
            ['role' => 'user', 'content' => $request->message]
        ],
        'stream' => false,
    ]);

    $bot_response = $response->json()['message'] ?? 'No response received';
    $bot_response = is_array($bot_response) ? json_encode($bot_response) : $bot_response;

    return response()->json([
        'response' => $bot_response
    ]);
}
}