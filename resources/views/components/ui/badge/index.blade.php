@props([
    'color' => null,
    'status' => null,
    'variant' => 'soft',
    'rounded' => false,
    'size' => 'md',
    'icon' => null,
])

@php
    $color ??= match ($status) {
        'submitted' => 'yellow',
        'under_review' => 'blue',
        'approved' => 'green',
        'rejected' => 'red',
        default => 'neutral',
    };

    $baseClasses = 'inline-flex w-fit items-center font-medium whitespace-nowrap ring-1 ring-inset';

    $sizeClasses = match ($size) {
        'sm' => 'text-xs py-0.5 px-2 gap-1',
        'lg' => 'text-sm py-1.5 px-3 gap-2',
        default => 'text-sm py-1 px-2.5 gap-1.5',
    };

    $roundedClasses = $rounded ? 'rounded-full' : 'rounded-md';

    if ($variant === 'solid') {
        $colorClasses = match ($color) {
            'accent' => 'bg-accent text-white ring-accent/10',
            'red' => 'bg-red-500 text-white ring-red-500/10',
            'green' => 'bg-green-500 text-white ring-green-500/10',
            'blue' => 'bg-blue-500 text-white ring-blue-500/10',
            'yellow' => 'bg-yellow-500 text-white ring-yellow-500/10',
            'rose' => 'bg-rose-500 text-white ring-rose-500/10',
            default => 'bg-neutral-600 text-white ring-neutral-600/10',
        };
    } else {
        // Soft variant (default)
        $colorClasses = match ($color) {
            'accent' => 'bg-accent/10 text-[var(--color-accent)] ring-accent/20',
            'red' => 'bg-red-50 text-red-700 ring-red-600/10',
            'green' => 'bg-green-50 text-green-700 ring-green-600/20',
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
            'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
            'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/10',
            default => 'bg-neutral-50 text-neutral-600 ring-neutral-500/10',
        };
    }

    $classes = trim("$baseClasses $sizeClasses $roundedClasses $colorClasses");
@endphp

<span {{ $attributes->except('status')->merge(['class' => $classes]) }}>
    @if ($icon)
        <x-dynamic-component :component="'icons.' . $icon" class="w-4 h-4" />
    @endif
    {{ $slot }}
</span>
