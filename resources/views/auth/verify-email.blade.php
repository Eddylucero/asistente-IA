<x-guest-layout :header="'Verifica tu correo'" :subheader="'Te enviamos un enlace de verificación. Ábrelo para activar tu cuenta.'">
    @if (session('status') === 'verification-link-sent')
        <x-auth-session-status class="mb-3" status="Te enviamos un enlace nuevo al correo con el que te registraste." />
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-submit-button icon="send">Reenviar enlace</x-submit-button>
    </form>

    <form class="mt-4 text-center" method="POST" action="{{ route('logout') }}" data-confirm-logout>
        @csrf
        <button class="font-label-md text-label-md text-primary hover:underline text-sm" type="submit">Cerrar sesión</button>
    </form>
</x-guest-layout>
