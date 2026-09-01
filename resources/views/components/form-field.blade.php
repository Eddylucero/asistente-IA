@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'height' => 'h-12',
    'toggle' => false,
    'messages' => null,
])

@php
    $id = $attributes->get('id', $name);
    $messages = (array) ($messages ?? $errors->get($name));

    $input = array_filter([
        'id' => $id,
        'name' => $name,
        'type' => $type,
    ]);
@endphp

<div class="space-y-1">
    <label class="font-label-md text-label-md text-on-surface-variant block ml-1 text-sm" for="{{ $id }}">{{ $label }}</label>

    <div class="relative group">
        @if ($icon)
            <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline-variant group-focus-within:text-primary transition-colors">{{ $icon }}</span>
        @endif

        <input {{ $attributes->except('id')->class([
            'w-full bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-[1.25rem] focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm transition-shadow placeholder:text-outline-variant',
            $height,
            $icon ? 'pl-12' : 'pl-4',
            $toggle ? 'pr-12' : 'pr-4',
            'input-invalid' => $messages !== [],
        ])->merge($input) }} />

        @if ($toggle)
            <button
                class="absolute right-4 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant transition-colors"
                type="button"
                data-password-toggle="{{ $id }}"
                aria-label="Mostrar u ocultar la contraseña"
            >
                <span class="material-symbols-outlined">visibility</span>
            </button>
        @endif
    </div>

    @foreach ($messages as $message)
        <p class="text-error text-xs mt-1">{{ $message }}</p>
    @endforeach
</div>
