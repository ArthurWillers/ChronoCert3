@props(['align' => 'left'])
@php
    $alignment = [
        'left' => 'text-left justify-start',
        'center' => 'text-center justify-center',
        'right' => 'text-end justify-end',
    ][$align] ?? 'text-left justify-start';
@endphp
<div {{ $attributes->merge(['class' => "min-w-0 px-4 lg:px-6 py-4 overflow-hidden flex flex-col $alignment"]) }}>
    {{ $slot }}
</div>

