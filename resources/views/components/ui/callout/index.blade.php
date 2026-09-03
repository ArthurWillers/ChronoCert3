@props([
    'color' => 'accent', // accent, red, green, yellow, neutral
    'icon' => null,
    'title' => '',
])

@php
    $colorClasses = match ($color) {
        'red' => 'bg-red-50 text-red-800 border-red-200',
        'green' => 'bg-green-50 text-green-800 border-green-200',
        'yellow' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'neutral' => 'bg-neutral-50 text-neutral-800 border-neutral-200',
        default => 'bg-accent/10 text-accent border-accent/20',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 border rounded-xl $colorClasses"]) }}>
    @if ($icon)
        <div class="shrink-0 mt-0.5">
            {{ $icon }}
        </div>
    @endif
    <div class="flex-1">
        @if ($title)
            <h3 class="text-sm font-semibold mb-1">{{ $title }}</h3>
        @endif
        <div class="text-sm opacity-90 leading-relaxed">
            {{ $slot }}
        </div>
    </div>
</div>

