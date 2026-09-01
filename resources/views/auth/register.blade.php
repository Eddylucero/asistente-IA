<x-guest-layout wide :header="'Crea tu cuenta.'" :subheader="'Estamos aquí para escucharte y acompañarte en tu viaje hacia el bienestar emocional.'">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('register') }}" class="space-y-4 flex flex-col" data-validate="register" novalidate>
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
            <x-form-field
                name="name"
                label="Nombre completo"
                icon="person"
                placeholder="Ej. Ana García"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
            />

            <x-form-field
                name="email"
                label="Correo electrónico"
                type="email"
                icon="mail"
                placeholder="tu@correo.com"
                :value="old('email')"
                required
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
                autocomplete="new-password"
            />

            <x-form-field
                name="password_confirmation"
                label="Confirmar contraseña"
                type="password"
                icon="lock"
                placeholder="••••••••"
                toggle
                required
                autocomplete="new-password"
            />
        </div>

        <x-submit-button class="mt-2" icon="arrow_forward">Crear cuenta</x-submit-button>
    </form>

    <x-form-divider>O continúa con</x-form-divider>

    <x-google-button />

    <p class="text-center font-body-md text-body-md text-on-surface-variant pt-2 text-sm">
        ¿Ya tienes cuenta?
        <a class="text-primary font-semibold hover:underline" href="{{ route('login') }}">Inicia sesión</a>
    </p>
</x-guest-layout>
