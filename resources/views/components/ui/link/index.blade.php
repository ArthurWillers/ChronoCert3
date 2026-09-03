@props([
    'variant' => 'underline', // 'underline', 'ghost'
    'external' => false,
])

@php
    $baseClasses =
        'font-semibold transition-colors duration-300 text-neutral-700 decoration-neutral-700/30 hover:decoration-neutral-700';

    // Classes de variante (com ou sem sublinhado inicial)
    $variantClasses = match ($variant) {
        'ghost' => 'hover:underline underline-offset-4',
        default => 'underline underline-offset-4',
    };

    $finalClasses = implode(' ', [$baseClasses, $variantClasses]);
@endphp

<a {{ $attributes->merge(['class' => $finalClasses]) }}>
    {{ $slot }}
</a>

