<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ChatHistory;
use Ramsey\Uuid\Uuid;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $user = $request->user();
        $session_id = $request->session_id ?? (string) Uuid::uuid4();

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

        $response = Http::post('http://localhost:11434/api/chat', [
            'model' => 'mistral',
            'messages' => $messages,
            'stream' => false,
        ]);

        // Debugga svaret från API:et om felet kvarstår
        $bot_response = $response->json()['message'] ?? 'No response received';

        // Se till att bot_response är en sträng
        $bot_response = is_array($bot_response) ? json_encode($bot_response) : $bot_response;

        ChatHistory::create([
            'user_id' => $user->id,
            'session_id' => $session_id,
            'user_message' => $request->message,
            'bot_response' => $bot_response,
        ]);

        return response()->json(['session_id' => $session_id, 'response' => $bot_response]);
    }
}