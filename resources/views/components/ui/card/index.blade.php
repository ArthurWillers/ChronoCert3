@props([
    'tag' => 'div',
    'href' => null,
    'size' => null,
])

@php
    $tag = $href ? 'a' : $tag;
    $isInteractive = (bool) $href;

    $baseClasses = 'border border-neutral-200 bg-white shadow-sm transition-colors duration-150';
    
    $sizeClasses = match ($size) {
        'sm' => 'rounded-lg p-3 sm:p-4',
        default => 'rounded-lg p-4 sm:p-5',
    };

    $interactiveClasses = $isInteractive
        ? 'hover:border-neutral-300 hover:shadow'
        : '';

    $classes = trim("$baseClasses $sizeClasses $interactiveClasses");
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
