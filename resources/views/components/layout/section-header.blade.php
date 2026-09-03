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

<div class="flex items-center gap-3 mb-6">
    <div class="w-12 h-12 {{ $bgClass }} rounded-lg flex items-center justify-center">
        <x-dynamic-component :component="$icon" class="w-6 h-6" />
    </div>
    <h3 class="text-xl font-bold text-neutral-800">{{ $title }}</h3>
</div>

