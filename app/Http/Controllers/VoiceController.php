<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoiceController extends Controller
{
    public function index(): View
    {
        $conversation = auth()->user()->latestConversationOrNull();

        return view('voice.index', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->oldest()->get() ?? collect(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = auth()->user()->latestConversationOrNull()
            ?? auth()->user()->conversations()->create();

        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['content'],
        ]);

        $conversation->rememberTitle($validated['content']);

        return response()->json(['message' => $message->toPayload()], 201);
    }
}
