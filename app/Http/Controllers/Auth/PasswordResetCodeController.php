<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PasswordResetCodeController extends Controller
{
    private const SESSION_EMAIL = 'password_reset.email';

    private const SESSION_TOKEN = 'password_reset.token';

    public function showEmailForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.exists' => 'Este correo no se encuentra registrado.',
        ]);

        $email = $validated['email'];

        if (! $this->dispatchCode($email)) {
            return back()->withInput()->with('swal', [
                'icon' => 'error',
                'title' => 'No pudimos enviar el correo',
                'text' => 'Revisa tu conexión e inténtalo de nuevo en un momento.',
            ]);
        }

        $request->session()->put(self::SESSION_EMAIL, $email);
        $request->session()->forget(self::SESSION_TOKEN);

        return redirect()->route('password.code')->with('swal', [
            'icon' => 'success',
            'toast' => false,
            'title' => 'Código enviado',
            'text' => 'Te enviamos un código de 6 dígitos a '.$email.'. Revisa también tu carpeta de spam.',
        ]);
    }

    public function showCodeForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if (! $email) {
            return $this->restart('Empecemos de nuevo', 'Necesitamos tu correo para enviarte el código.');
        }

        return view('auth.verify-code', [
            'email' => $email,
            'minutes' => PasswordResetCode::TTL_MINUTES,
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if (! $email) {
            return $this->restart('Empecemos de nuevo', 'Necesitamos tu correo para enviarte el código.');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código que te enviamos.',
            'code.digits' => 'El código tiene 6 dígitos.',
        ]);

        $record = PasswordResetCode::activeFor($email);

        if (! $record || ! $record->matches($validated['code'])) {
            return back()->with('swal', [
                'icon' => 'error',
                'title' => 'Código incorrecto',
                'text' => 'Revisa el código o solicita uno nuevo. Caduca a los '.PasswordResetCode::TTL_MINUTES.' minutos.',
            ]);
        }

        $request->session()->put(self::SESSION_TOKEN, $record->markVerified());

        return redirect()->route('password.reset.form')->with('swal', [
            'icon' => 'success',
            'title' => 'Código verificado',
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if (! $email) {
            return $this->restart('Empecemos de nuevo', 'Necesitamos tu correo para enviarte el código.');
        }

        if (! User::where('email', $email)->exists()) {
            return $this->restart('Este correo no se encuentra registrado', 'Verifica el correo e inténtalo de nuevo.');
        }

        if (! $this->dispatchCode($email)) {
            return back()->with('swal', [
                'icon' => 'error',
                'title' => 'No pudimos enviar el correo',
                'text' => 'Inténtalo de nuevo en un momento.',
            ]);
        }

        $request->session()->forget(self::SESSION_TOKEN);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Te enviamos un código nuevo',
        ]);
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedRecord($request)) {
            return $this->restart('Verifica tu código', 'El código caducó o aún no lo has confirmado.');
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get(self::SESSION_EMAIL),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $record = $this->verifiedRecord($request);

        if (! $record) {
            return $this->restart('Verifica tu código', 'El código caducó o aún no lo has confirmado.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.required' => 'Ingresa una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $user = User::where('email', $record->email)->first();

        PasswordResetCode::where('email', $record->email)->delete();
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_TOKEN]);

        if (! $user) {
            return $this->restart('No encontramos esa cuenta', 'Verifica el correo e inténtalo de nuevo.');
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('swal', [
            'icon' => 'success',
            'toast' => false,
            'title' => '¡Contraseña actualizada!',
            'text' => 'Ya puedes iniciar sesión con tu contraseña nueva.',
        ]);
    }

    private function dispatchCode(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        try {
            $code = PasswordResetCode::generateFor($email);

            Mail::to($email)->send(new PasswordResetCodeMail($code, $user->name));
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    private function verifiedRecord(Request $request): ?PasswordResetCode
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        $token = $request->session()->get(self::SESSION_TOKEN);

        if (! $email || ! $token) {
            return null;
        }

        $record = PasswordResetCode::where('email', $email)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($token, $record->code_hash)) {
            return null;
        }

        return $record;
    }

    private function restart(string $title, string $text): RedirectResponse
    {
        session()->forget([self::SESSION_EMAIL, self::SESSION_TOKEN]);

        return redirect()->route('password.request')->with('swal', [
            'icon' => 'info',
            'toast' => false,
            'title' => $title,
            'text' => $text,
        ]);
    }
}
