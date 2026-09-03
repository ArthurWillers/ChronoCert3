@props([
    'id' => null,
    'open' => false,
    'headClass' => '',
    'panelClass' => '',
    'innerClass' => '',
])

@php
    $accordionId = $id ?? 'accordion-' . \Illuminate\Support\Str::uuid();
@endphp

<div
    {{ $attributes->merge(['class' => 't-acc']) }}
    x-data="{ open: @js($open), toggle() { this.open = !this.open; } }"
    data-open="{{ $open ? 'true' : 'false' }}"
    x-bind:data-open="open"
>
    <button
        type="button"
        class="t-acc-head w-full cursor-pointer border-0 bg-transparent p-0 text-left focus:outline-none {{ $headClass }}"
        aria-controls="{{ $accordionId }}"
        x-bind:aria-expanded="open"
        @click="toggle()"
    >
        {{ $trigger }}
    </button>

    <div
        id="{{ $accordionId }}"
        class="t-acc-panel {{ $panelClass }}"
    >
        <div class="t-acc-panel-inner {{ $innerClass }}">
            {{ $slot }}
        </div>
    </div>
</div>

