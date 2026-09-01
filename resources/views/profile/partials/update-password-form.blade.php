<section class="rounded-2xl bg-surface p-stack-md shadow-sm ring-1 ring-outline-variant/10">
    <header class="mb-stack-md">
        <h2 class="text-body-lg font-label-md text-on-surface">Contraseña</h2>
        <p class="text-label-md font-label-md text-on-surface-variant">Usa una contraseña larga que no hayas utilizado antes.</p>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4" data-validate="profile-password" novalidate>
        @csrf
        @method('put')

        <x-form-field
            id="current_password"
            name="current_password"
            label="Contraseña actual"
            type="password"
            icon="lock"
            placeholder="••••••••"
            toggle
            autocomplete="current-password"
            :messages="$errors->updatePassword->get('current_password')"
        />

        <x-form-field
            id="new_password"
            name="password"
            label="Contraseña nueva"
            type="password"
            icon="lock_reset"
            placeholder="••••••••"
            toggle
            autocomplete="new-password"
            :messages="$errors->updatePassword->get('password')"
        />

        <x-form-field
            id="new_password_confirmation"
            name="password_confirmation"
            label="Confirmar contraseña nueva"
            type="password"
            icon="lock_reset"
            placeholder="••••••••"
            toggle
            autocomplete="new-password"
            :messages="$errors->updatePassword->get('password_confirmation')"
        />

        <x-submit-button class="sm:w-auto sm:self-start sm:px-6" icon="check">Actualizar contraseña</x-submit-button>
    </form>
</section>
