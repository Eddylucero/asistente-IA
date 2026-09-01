<x-guest-layout :header="'Revisa tu correo'" :subheader="'Escribe el código de 6 dígitos que enviamos para confirmar que eres tú.'">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <x-password-steps :current="2" />

    <p class="font-body-md text-body-md text-on-surface-variant text-sm mb-4">
        Enviado a <span class="text-on-surface font-semibold">{{ $email }}</span>.
        El código caduca en {{ $minutes }} minutos.
    </p>

    <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-4 flex flex-col" data-validate="verify-code" novalidate>
        @csrf

        <x-form-field
            name="code"
            label="Código de verificación"
            icon="pin"
            height="h-14"
            class="text-headline-md text-center tracking-[0.6em] placeholder:tracking-[0.4em]"
            placeholder="······"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            required
            autofocus
        />

        <x-submit-button class="mt-2" icon="arrow_forward">Verificar código</x-submit-button>
    </form>

    <x-form-divider>¿No te llegó?</x-form-divider>

    <form method="POST" action="{{ route('password.code.resend') }}" data-confirm-resend>
        @csrf
        <button class="w-full h-11 bg-surface-container-lowest text-on-surface font-label-md text-label-md rounded-[1.25rem] shadow-sm hover:shadow-md hover:bg-surface-container-low transition-all flex items-center justify-center gap-3" type="submit">
            <span class="material-symbols-outlined text-[20px]">refresh</span>
            Reenviar código
        </button>
    </form>

    <p class="text-center font-body-md text-body-md text-on-surface-variant pt-4 text-sm">
        ¿Correo equivocado?
        <a class="text-primary font-semibold hover:underline" href="{{ route('password.request') }}">Cámbialo aquí</a>
    </p>
</x-guest-layout>
