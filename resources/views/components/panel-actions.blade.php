<div {{ $attributes->class(['flex items-center gap-1']) }}>
    <x-theme-toggle variant="plain" />

    <form class="lg:hidden" method="POST" action="{{ route('logout') }}" data-confirm-logout>
        @csrf
        <button
            class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:text-error hover:bg-error-container/40 hover:scale-105 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 transition-all duration-soft ease-calm motion-reduce:transition-none motion-reduce:hover:scale-100 motion-reduce:active:scale-100"
            type="submit"
            aria-label="Cerrar sesión"
        >
            <span class="material-symbols-outlined">logout</span>
        </button>
    </form>
</div>
