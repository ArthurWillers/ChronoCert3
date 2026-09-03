@props([
    'name' => '',
    'hasError' => false,
])

@php
    $baseClasses = 'h-11 w-full border text-sm rounded-xl block py-2.5 px-4 bg-white disabled:shadow-none shadow-xs focus:shadow-lg text-neutral-700 disabled:text-neutral-400 outline-none focus:border-accent focus:ring-2 focus:ring-accent/40 transition-colors duration-300';

    $isError = $hasError || ($name && $errors->has($name));
    $errorClasses = $isError
        ? 'border-red-500 focus:border-red-500 focus:ring-red-400/30'
        : 'border-neutral-200';

    $classes = $baseClasses . ' ' . $errorClasses;
@endphp

<select @if($name) id="{{ $name }}" name="{{ $name }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</select>

