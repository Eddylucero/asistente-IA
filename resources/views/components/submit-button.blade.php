@props(['icon' => null])

<button {{ $attributes->class([
    'w-full h-11 bg-secondary text-on-secondary font-label-md text-label-md rounded-[1.25rem] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2',
])->merge(['type' => 'submit']) }}>
    {{ $slot }}
    @if ($icon)
        <span class="material-symbols-outlined">{{ $icon }}</span>
    @endif
</button>
