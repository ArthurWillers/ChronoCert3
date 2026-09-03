@props([
    'name',
])

<div {{ $attributes->class('contents') }}
    x-data="{ loading: false }"
    x-on:click="$el.querySelector('button[disabled]') || $dispatch('modal-open', '{{ $name }}')"
>
    {{ $slot }}
</div>

