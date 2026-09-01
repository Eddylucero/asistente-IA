<x-guest-layout :header="'Confirma tu contraseña'" :subheader="'Esta es un área protegida. Vuelve a escribir tu contraseña para continuar.'">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4 flex flex-col">
        @csrf

        <x-form-field
            name="password"
            label="Contraseña"
            type="password"
            icon="lock"
            placeholder="••••••••"
            toggle
            required
            autofocus
            autocomplete="current-password"
        />

        <x-submit-button class="mt-2" icon="check">Confirmar</x-submit-button>
    </form>
</x-guest-layout>
