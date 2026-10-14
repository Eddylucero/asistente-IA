@props(['fit' => 'object-contain p-stack-sm'])

<div {{ $attributes->merge(['class' => 'relative']) }} data-chat-avatar role="img" aria-label="Lumi, asistente virtual">
    <div class="absolute inset-0 transition-opacity duration-300 motion-reduce:transition-none" data-avatar-greeting>
        <video class="absolute inset-0 w-full h-full {{ $fit }}" autoplay muted loop playsinline preload="auto" aria-hidden="true">
            <source src="{{ asset('images/mente/saludo-move.mp4') }}" type="video/mp4" />
        </video>
    </div>
    <div class="opacity-0 absolute inset-0 transition-opacity duration-300 motion-reduce:transition-none" data-avatar-talking>
        <video class="absolute inset-0 w-full h-full {{ $fit }}" muted loop playsinline preload="auto" aria-hidden="true">
            <source src="{{ asset('images/mente/avatar-move.mp4') }}" type="video/mp4" />
        </video>
    </div>
</div>
