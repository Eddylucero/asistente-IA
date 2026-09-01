<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('swal', [
                'icon' => 'error',
                'title' => 'Google no está configurado',
                'text' => 'Falta definir GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el archivo .env.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->with('swal', [
                'icon' => 'info',
                'title' => 'Inicio de sesión cancelado',
                'text' => 'No se completó la autorización con Google.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->with('swal', [
                'icon' => 'error',
                'title' => 'No pudimos conectar con Google',
                'text' => 'Inténtalo nuevamente en unos momentos.',
            ]);
        }

        $email = $googleUser->getEmail();

        if (blank($email)) {
            return redirect()->route('login')->with('swal', [
                'icon' => 'error',
                'title' => 'Cuenta sin correo',
                'text' => 'Tu cuenta de Google no expone un correo electrónico.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        $isNew = $user === null;

        if ($isNew) {
            $user = new User([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
            ]);

            $user->password = null;
            $user->email_verified_at = now();
        }

        $user->google_id = $googleUser->getId();
        $user->avatar = $googleUser->getAvatar();
        $user->email_verified_at ??= now();
        $user->save();

        if ($isNew) {
            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false))->with('swal', $isNew
            ? [
                'icon' => 'success',
                'toast' => false,
                'title' => '¡Cuenta creada!',
                'text' => 'Bienvenido a Mente, '.$user->name.'. Tu santuario digital te espera.',
            ]
            : [
                'icon' => 'success',
                'title' => '¡Bienvenido de vuelta, '.$user->name.'!',
            ]);
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
