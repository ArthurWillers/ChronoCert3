@props([
    'label' => '',
    'name' => '',
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div>
    <label @if($inputId) for="{{ $inputId }}" @endif class="flex cursor-pointer items-center gap-x-2 {{ $attributes->get('class') }}">
        <input
            @if($inputId) id="{{ $inputId }}" @endif
            name="{{ $name }}"
            type="checkbox"
            class="peer sr-only"
            {{ $attributes->except('class') }}
        />

        <span aria-hidden="true" class="t-check">
            <x-heroicon-o-check class="size-3.5" />
        </span>

        @if ($label)
            <span class="text-sm font-medium text-neutral-700">
                {{ $label }}
            </span>
        @endif

        {{ $slot }}
    </label>

    {{-- Mensagem de Erro --}}
    <x-error :name="$name" class="mt-3!" />
</div>
