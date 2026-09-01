@props(['current' => 1])

@php
    $steps = [
        1 => ['label' => 'Correo', 'icon' => 'mail'],
        2 => ['label' => 'Código', 'icon' => 'pin'],
        3 => ['label' => 'Contraseña', 'icon' => 'lock_reset'],
    ];
@endphp

<ol class="flex items-center justify-between gap-1 mb-6" aria-label="Progreso de recuperación">
    @foreach ($steps as $number => $step)
        @php
            $done = $number < $current;
            $active = $number === $current;
        @endphp

        <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
            <span
                @class([
                    'w-9 h-9 shrink-0 rounded-full flex items-center justify-center transition-colors',
                    'bg-primary text-on-primary shadow-sm' => $active,
                    'bg-secondary-container text-on-secondary-container' => $done,
                    'bg-surface-container text-outline' => ! $active && ! $done,
                ])
                @if ($active) aria-current="step" @endif
            >
                <span class="material-symbols-outlined text-[20px]">{{ $done ? 'check' : $step['icon'] }}</span>
            </span>

            <span @class([
                'font-label-md text-label-md text-xs hidden sm:inline',
                'text-on-surface font-semibold' => $active,
                'text-on-surface-variant' => ! $active,
            ])>{{ $step['label'] }}</span>

            @unless ($loop->last)
                <span @class([
                    'flex-1 h-px ml-1',
                    'bg-secondary-container' => $done,
                    'bg-outline-variant/50' => ! $done,
                ])></span>
            @endunless
        </li>
    @endforeach
</ol>
