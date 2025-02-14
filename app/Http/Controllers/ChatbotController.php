<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\ChatHistory;
use App\Models\Session as ChatSession; // Lägg till detta för att hantera sessions-tabellen
use Ramsey\Uuid\Uuid;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $user = $request->user();

        // Kontrollera om requesten skickar en session_id
        if ($request->has('session_id')) {
            $session_id = $request->session_id;
        } else {
            // Om ingen session_id skickas, kolla om användaren redan har en session
            if (Session::has('chat_session_id')) {
                $session_id = Session::get('chat_session_id');
            } else {
                // Skapa en ny session_id och spara den i Laravel-sessionen
                $session_id = (string) Uuid::uuid4();
                Session::put('chat_session_id', $session_id);
            }
        }

        \Log::info('Using session ID:', ['session_id' => $session_id]);

        // 🔹 Kontrollera om sessionen redan finns i sessions-tabellen
        $sessionExists = DB::table('sessions')->where('id', $session_id)->exists();

        // 🔹 Om sessionen inte finns, skapa den i sessions-tabellen
        if (!$sessionExists) {
            DB::table('sessions')->insert([
                'id' => $session_id,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'payload' => '', // Kan vara tomt, Laravel-sessioner använder detta
                'last_activity' => now()->timestamp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Created new session:', ['session_id' => $session_id]);
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