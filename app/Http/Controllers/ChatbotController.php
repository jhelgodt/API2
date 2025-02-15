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

        // 🔹 Hämta session_id från Laravel-sessionen eller requesten
        if ($request->has('session_id')) {
            $session_id = $request->session_id;
        } else {
            $session_id = Session::get('chat_session_id');
        }

        // 🔹 Om session_id fortfarande saknas, returnera fel
        if (!$session_id) {
            return response()->json(['error' => 'Session not found. Please log in again.'], 401);
        }

        \Log::info('Using session ID:', ['session_id' => $session_id]);

        // 🔹 Kontrollera om sessionen existerar
        $sessionExists = DB::table('sessions')->where('id', $session_id)->exists();

        if (!$sessionExists) {
            return response()->json(['error' => 'Invalid session. Please log in again.'], 401);
        }

        // 🔹 Hämta tidigare meddelanden från chat_histories-tabellen
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

        // 🔹 Spara konversationen i chat_histories-tabellen
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
}