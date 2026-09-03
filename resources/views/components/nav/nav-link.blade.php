@props([
    'href' => '#',
    'current' => null,
    'active' => null,
])

@php
    $current ??= $active ?? false;
@endphp

<a href="{{ $href }}" {{ $attributes->except('active')->class([
        // Classes base, sempre aplicadas
        'rounded-lg min-h-11 lg:min-h-8 gap-3 flex relative items-center w-full text-base lg:text-sm [&>svg]:size-5 lg:[&>svg]:size-4 px-3 text-start',
        // Classes para o estado ATIVO
        'bg-neutral-50 border border-neutral-300' => $current,
        // Classes para o estado INATIVO
        'text-neutral-500 hover:bg-black/7 hover:text-neutral-800' => !$current,
    ]) }}>
    {{ $slot }}
</a>
