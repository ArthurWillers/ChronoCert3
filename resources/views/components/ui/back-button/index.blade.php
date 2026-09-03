@props(['fallback', 'text' => 'Voltar', 'icon' => 'heroicon-o-arrow-left'])

@php
    $previousUrl = url()->previous();
    $currentUrl = url()->current();
    
    // Se a URL anterior for igual à atual, veio de um form, ou sem referer
    $fromForm = str_contains($previousUrl, '/create') || str_contains($previousUrl, '/edit');
    $href = ($previousUrl === $currentUrl || $previousUrl === url('/') || $fromForm) ? $fallback : $previousUrl;
@endphp

<x-button color="outline" href="{{ $href }}" {{ $attributes->merge(['class' => 'bg-white']) }}>
    @if($icon)
        <x-dynamic-component :component="$icon" class="size-4" />
    @endif
    <span>{{ $text }}</span>
</x-button>

