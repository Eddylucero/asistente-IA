<?php

namespace App\Http\Controllers;

use App\Services\LocalSpeech;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Throwable;

class SpeechController extends Controller
{
    public function preference(Request $request, LocalSpeech $speech): \Illuminate\Http\JsonResponse
    {
        return response()->json(['voice' => $request->user()->tts_voice ?: $speech->defaultVoice()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, LocalSpeech $speech): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'voice' => ['required', Rule::in(array_keys(config('speech.voices', [])))],
        ]);

        abort_unless($speech->isAvailable($validated['voice']), 422, 'La voz seleccionada no está disponible.');

        try {
            $audio = $speech->synthesize($validated['text'], $validated['voice']);
        } catch (Throwable $exception) {
            report($exception);
            abort(503, 'No pudimos generar la voz. Inténtalo de nuevo o elige otra voz en tu perfil.');
        }

        return response($audio)->header('Content-Type', 'audio/wav')
            ->header('Cache-Control', 'private, no-store');
    }
}
