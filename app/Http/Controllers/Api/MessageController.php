<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Display a single message. Marks it as read automatically.
     */
    public function show(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        // Ensure the message belongs to this user
        $agent = $user->agent;
        if ($agent && $message->agent_id !== $agent->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        // Auto-mark as read
        if (!$message->lu) {
            $message->update(['lu' => true]);
        }

        $message->load('sender');

        return response()->json([
            'data' => $message,
        ]);
    }
}
