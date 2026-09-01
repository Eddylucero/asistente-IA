@props(['variant' => 'icon'])

@php
    $base = 'group text-on-surface-variant hover:text-on-surface hover:scale-105 active:scale-95'
        .' focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50'
        .' transition-all duration-soft ease-calm'
        .' motion-reduce:transition-none motion-reduce:hover:scale-100 motion-reduce:active:scale-100';

    $shape = match ($variant) {
        'row' => 'w-full flex items-center gap-3 h-11 px-stack-md rounded-xl hover:bg-surface-container-highest/60 font-label-md text-label-md',
        'plain' => 'inline-flex items-center justify-center w-10 h-10 shrink-0 rounded-full hover:bg-surface-container-highest/70',
        default => 'inline-flex items-center justify-center w-11 h-11 rounded-full bg-surface-container/70 hover:bg-surface-container-high hover:shadow-md shadow-sm',
    };
@endphp

<button
    type="button"
    data-theme-toggle
    aria-pressed="false"
    aria-label="Cambiar a modo oscuro"
    title="Modo oscuro"
    {{ $attributes->class([$base, $shape]) }}
>
    <span class="relative block w-5 h-5 shrink-0" aria-hidden="true">
        <span class="material-symbols-outlined text-[20px] leading-5 absolute inset-0 transition-all duration-calm ease-calm rotate-0 scale-100 opacity-100 dark:-rotate-90 dark:scale-0 dark:opacity-0 motion-reduce:transition-none">light_mode</span>
        <span class="material-symbols-outlined text-[20px] leading-5 absolute inset-0 transition-all duration-calm ease-calm rotate-90 scale-0 opacity-0 dark:rotate-0 dark:scale-100 dark:opacity-100 motion-reduce:transition-none">dark_mode</span>
    </span>

    @if ($variant === 'row')
        <span class="dark:hidden">Modo oscuro</span>
        <span class="hidden dark:inline">Modo claro</span>
    @endif
</button>
