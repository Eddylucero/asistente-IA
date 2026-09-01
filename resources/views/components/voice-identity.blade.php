@props(['align' => 'left'])

<div {{ $attributes->class([
    'flex flex-col gap-0 sm:gap-unit',
    'items-center text-center' => $align === 'center',
]) }}>
    <span class="font-label-md text-label-sm sm:text-label-md uppercase tracking-[0.2em] text-primary">Sesión activa</span>
    <h1 class="font-display-lg text-headline-lg sm:text-display-lg tracking-tight text-on-background">Dra. Mente</h1>
    <div class="mt-0 sm:mt-unit flex items-center gap-2">
        <span class="h-2 w-2 animate-pulse rounded-full bg-secondary"></span>
        <span class="font-body-md text-label-sm sm:text-body-md text-on-surface-variant" data-connection-status>Lista para escucharte</span>
    </div>
</div>
