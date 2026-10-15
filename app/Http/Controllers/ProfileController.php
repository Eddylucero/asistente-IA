<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\LocalSpeech;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user(), 'localVoices' => app(LocalSpeech::class)->voices()]);
    }

    public function updateVoice(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updateVoice', [
            'tts_voice' => ['bail', 'required', 'string', 'max:512', function ($attribute, $value, $fail) {
                if ($value !== 'auto' && ! preg_match('/^browser:[^\x00-\x1F]+$/u', $value)
                    && ! array_key_exists($value, config('speech.voices', []))) {
                    $fail('Elige una voz disponible.');
                }
            }],
        ]);
        $request->user()->update(['tts_voice' => $validated['tts_voice'] === 'auto' ? null : $validated['tts_voice']]);

        return redirect()->route('profile.edit')->with('swal', ['icon' => 'success', 'title' => 'Voz de Lumi actualizada']);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('swal', [
            'icon' => 'success',
            'title' => 'Datos actualizados',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('swal', [
            'icon' => 'success',
            'toast' => false,
            'title' => 'Cuenta eliminada',
            'text' => 'Gracias por acompañarnos. Tus datos se borraron por completo.',
        ]);
    }
}
