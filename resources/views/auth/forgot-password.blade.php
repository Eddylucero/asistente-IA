<x-guest-layout :header="'¿Olvidaste tu contraseña?'" :subheader="'Escribe tu correo y te enviaremos un código de 6 dígitos para recuperarla.'">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <x-password-steps :current="1" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4 flex flex-col" data-validate="forgot-password" novalidate>
        @csrf

        <x-form-field
            name="email"
            label="Correo electrónico"
            type="email"
            icon="mail"
            placeholder="tu@correo.com"
            :value="old('email')"
            required
            autofocus
            autocomplete="username"
        />

        <x-submit-button class="mt-2" icon="send">Enviar código</x-submit-button>
    </form>

    <p class="text-center font-body-md text-body-md text-on-surface-variant pt-4 text-sm">
        ¿Ya la recordaste?
        <a class="text-primary font-semibold hover:underline" href="{{ route('login') }}">Inicia sesión</a>
    </p>
</x-guest-layout>
