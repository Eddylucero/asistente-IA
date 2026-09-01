<button
    type="button"
    data-drawer-toggle
    aria-controls="panel-drawer"
    aria-expanded="false"
    aria-label="Abrir menú"
    {{ $attributes->class([
        'lg:hidden w-10 h-10 shrink-0 flex items-center justify-center rounded-full',
        'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest/70',
        'hover:scale-105 active:scale-95',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50',
        'transition-all duration-soft ease-calm',
        'motion-reduce:transition-none motion-reduce:hover:scale-100 motion-reduce:active:scale-100',
    ]) }}
>
    <span class="material-symbols-outlined">menu</span>
</button>
