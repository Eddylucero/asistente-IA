<?php

namespace App\Http\Controllers;

use App\Services\Assistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoiceController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'conversation' => ['nullable', 'integer'],
            'new' => ['sometimes', 'boolean'],
        ]);
        $conversation = isset($validated['conversation'])
            ? $request->user()->conversations()->findOrFail($validated['conversation'])
            : (($validated['new'] ?? false) ? null : $request->user()->latestConversationOrNull());

        return view('voice.index', [
            'conversation' => $conversation,
        ]);
    }

    public function store(Request $request, Assistant $assistant, ConversationController $conversations): JsonResponse
    {
        $response = $conversations->store($request, $assistant);
        if ($response->isSuccessful()) {
            $payload = $response->getData(true);
            $payload['message'] = $payload['messages'][0];
            $response->setData($payload)->setStatusCode(201);
        }

        return $response;
    }
}
