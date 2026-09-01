<x-guest-layout :header="'Crea tu contraseña nueva'" :subheader="'Último paso. Elige una contraseña que no hayas usado antes.'">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <x-password-steps :current="3" />

    <p class="font-body-md text-body-md text-on-surface-variant text-sm mb-4">
        Cuenta: <span class="text-on-surface font-semibold">{{ $email }}</span>
    </p>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4 flex flex-col" data-validate="reset-password" novalidate>
        @csrf

        <x-form-field
            name="password"
            label="Contraseña nueva"
            type="password"
            icon="lock"
            placeholder="••••••••"
            toggle
            required
            autofocus
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

        <x-submit-button class="mt-2" icon="check">Guardar contraseña</x-submit-button>
    </form>
</x-guest-layout>
