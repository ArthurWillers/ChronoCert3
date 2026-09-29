@props(['align' => 'left'])
@php
    $alignment = [
        'left' => 'text-left justify-start',
        'center' => 'text-center justify-center',
        'right' => 'text-end justify-end',
    ][$align] ?? 'text-left justify-start';
@endphp
<div {{ $attributes->merge(['class' => "flex min-w-0 flex-col overflow-hidden px-3 py-3 $alignment sm:px-4 lg:px-5"]) }}>
    {{ $slot }}
</div>
