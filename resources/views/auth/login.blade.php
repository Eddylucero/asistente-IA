<x-guest-layout :header="'Bienvenido'" :subheader="'Estamos aquí para escucharte y acompañarte en tu viaje hacia el bienestar emocional.'">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4 flex flex-col" data-validate="login" novalidate>
        @csrf

        <x-form-field
            name="email"
            label="Correo Electrónico"
            type="email"
            icon="mail"
            placeholder="tu@correo.com"
            :value="old('email')"
            required
            autofocus
            autocomplete="username"
        />

        <x-form-field
            name="password"
            label="Contraseña"
            type="password"
            icon="lock"
            placeholder="••••••••"
            toggle
            required
            autocomplete="current-password"
        />

        <div class="flex items-center justify-end pt-1">
            @if (Route::has('password.request'))
                <a class="font-label-md text-label-md text-primary hover:text-primary-container transition-colors text-sm" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <x-submit-button class="mt-2" icon="arrow_forward">Ingresar</x-submit-button>
    </form>

    <x-form-divider>O ingresa con</x-form-divider>

    <x-google-button />

    <p class="text-center font-body-md text-body-md text-on-surface-variant pt-2 text-sm">
        ¿No tienes cuenta?
        @if (Route::has('register'))
            <a class="text-primary font-semibold hover:underline" href="{{ route('register') }}">Regístrate aquí</a>
        @endif
    </p>
</x-guest-layout>
