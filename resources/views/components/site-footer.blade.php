<footer {{ $attributes->class(['w-full bg-surface-container-lowest border-t border-outline-variant/20']) }}>
    <div class="max-w-container-max mx-auto px-margin-mobile lg:px-margin-desktop flex flex-col md:flex-row justify-between items-center gap-1">
        <div class="flex items-center gap-2 text-primary font-headline-md text-sm">
            <img alt="Logo" class="h-4 w-auto opacity-80" src="{{ config('brand.logo') }}"/>
            Mente
        </div>
        <p class="text-on-surface-variant text-xs font-label-sm">© {{ date('Y') }} Mente. Santuario digital para tu bienestar.</p>
    </div>
</footer>
