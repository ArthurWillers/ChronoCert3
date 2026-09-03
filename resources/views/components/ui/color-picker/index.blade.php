@props([
    'icon' => 'heroicon-o-swatch',
])

@php
    $tooltipText = $attributes->get('title', 'Escolher cor personalizada');
@endphp

<x-tooltip :text="$tooltipText" class="shrink-0">
    <div {{ $attributes->except(['x-model', 'class', 'title', 'style', 'x-bind:style', ':style']) }} class="relative w-10 h-10 rounded-full overflow-hidden shrink-0 border border-neutral-300 shadow-sm transition-transform hover:scale-110 focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2 focus-within:scale-110 flex items-center justify-center bg-white cursor-pointer group">
        <input type="color" {{ $attributes->only('x-model') }} class="absolute opacity-0 inset-0 w-full h-full cursor-pointer z-20 focus:outline-none">

        <!-- Base gradient background -->
        <div class="absolute inset-0" style="background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red);"></div>

        <!-- Dynamic color overlay from parent -->
        <div class="absolute inset-0" {{ $attributes->only(['style', 'x-bind:style', ':style']) }}></div>

        <div class="z-10 bg-white/30 rounded-full p-1 backdrop-blur-sm pointer-events-none relative">
            <x-dynamic-component :component="$icon" class="size-4 text-neutral-900 drop-shadow-md" />
        </div>
    </div>
</x-tooltip>

