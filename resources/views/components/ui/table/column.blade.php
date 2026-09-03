@props(['align' => 'left'])
@php
    $alignment = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-end',
    ][$align] ?? 'text-left';
@endphp
<div {{ $attributes->merge(['class' => "min-w-0 px-4 lg:px-6 py-4 $alignment"]) }}>
    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">{{ $slot }}</span>
</div>

