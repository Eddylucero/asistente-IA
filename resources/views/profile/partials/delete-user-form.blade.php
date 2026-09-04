<section class="rounded-2xl bg-error-container/20 p-stack-md shadow-sm ring-1 ring-error/20">
    <header class="mb-stack-md">
        <h2 class="text-body-lg font-label-md text-error">Eliminar cuenta</h2>
        <p class="text-label-md font-label-md text-on-surface-variant">
            Se borrarán tus conversaciones y mensajes de forma permanente. Escribe tu contraseña para confirmar.
        </p>
    </header>

    <form method="POST" action="{{ route('profile.destroy') }}" class="flex flex-col gap-4" data-validate="account-delete" data-confirm-account-delete novalidate>
        @csrf
        @method('delete')

        <x-form-field
            id="delete_password"
            name="password"
            label="Contraseña"
            type="password"
            icon="lock"
            placeholder="••••••••"
            toggle
            autocomplete="current-password"
            :messages="$errors->userDeletion->get('password')"
        />

        <button
            class="w-full h-11 sm:w-auto sm:self-start sm:px-6 bg-error text-on-error font-label-md text-label-md rounded-[1.25rem] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2"
            type="submit"
        >
            Eliminar mi cuenta
            <span class="material-symbols-outlined">delete_forever</span>
        </button>
    </form>
</section>
