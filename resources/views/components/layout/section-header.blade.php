@props(['icon', 'iconColor' => 'neutral', 'title'])

@php
    $colorClasses = [
        'red' => 'bg-red-100 text-red-600',
        'green' => 'bg-green-100 text-green-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'yellow' => 'bg-yellow-100 text-yellow-600',
        'neutral' => 'bg-neutral-100 text-neutral-600',
    ];

    $bgClass = $colorClasses[$iconColor] ?? $colorClasses['neutral'];
@endphp

<div class="mb-4 flex items-center gap-2.5">
    <div class="flex size-9 items-center justify-center rounded-lg {{ $bgClass }}">
        <x-dynamic-component :component="$icon" class="size-5" />
    </div>
    <h3 class="text-base font-semibold text-neutral-800">{{ $title }}</h3>
</div>
