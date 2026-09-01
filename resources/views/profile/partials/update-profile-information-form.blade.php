<section class="rounded-2xl bg-surface p-stack-md shadow-sm ring-1 ring-outline-variant/10">
    <header class="mb-stack-md">
        <h2 class="text-body-lg font-label-md text-on-surface">Información personal</h2>
        <p class="text-label-md font-label-md text-on-surface-variant">Actualiza tu nombre y tu correo electrónico.</p>
    </header>

    <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-4" data-validate="profile" novalidate>
        @csrf
        @method('patch')

        <x-form-field
            name="name"
            label="Nombre completo"
            icon="person"
            placeholder="Ej. Ana García"
            :value="old('name', $user->name)"
            required
            autocomplete="name"
        />

        <x-form-field
            name="email"
            label="Correo electrónico"
            type="email"
            icon="mail"
            placeholder="tu@correo.com"
            :value="old('email', $user->email)"
            required
            autocomplete="username"
        />

        <x-submit-button class="sm:w-auto sm:self-start sm:px-6" icon="save">Guardar cambios</x-submit-button>
    </form>
</section>
