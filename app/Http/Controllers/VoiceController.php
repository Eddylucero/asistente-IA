<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoiceController extends Controller
{
    public function index(): View
    {
        $conversation = auth()->user()->latestConversation();

        return view('voice.index', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->oldest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = auth()->user()->latestConversation();

        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['content'],
        ]);

        $conversation->rememberTitle($validated['content']);

        return response()->json(['message' => $message->toPayload()], 201);
    }
}
