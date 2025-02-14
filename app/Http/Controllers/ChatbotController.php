<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatHistory; // Saknades i din kod
use Ramsey\Uuid\Uuid; // Saknades i din kod

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $user = $request->user();
        $session_id = $request->session_id ?? (string) \Ramsey\Uuid\Uuid::uuid4();
    
        $previousMessages = \App\Models\ChatHistory::where('user_id', $user->id)
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
    
        $response = Http::post('http://localhost:11434/api/chat', [
            'model' => 'mistral',
            'messages' => $messages,
            'stream' => false,
        ]);
    
        $bot_response = $response->json()['message'] ?? '';
        \App\Models\ChatHistory::create([
            'user_id' => $user->id,
            'session_id' => $session_id,
            'user_message' => $request->message,
            'bot_response' => $bot_response,
        ]);
    
        return response()->json(['session_id' => $session_id, 'response' => $bot_response]);
    }
}

