@props(['status'])

@if ($status)
    <div {{ $attributes->class(['font-label-md text-label-md text-secondary text-sm']) }}>
        {{ $status }}
    </div>
@endif
