@props([
    'label' => '',
    'name' => '',
    'value' => '',
    'id' => null,
])

@php
    $id = $id ?? 'radio-' . \Illuminate\Support\Str::uuid();
@endphp

<div>
    <label for="{{ $id }}" class="flex items-center gap-x-2.5 cursor-pointer">
        <input id="{{ $id }}" name="{{ $name }}" type="radio" value="{{ $value }}"
            {{ $attributes->merge(['class' => 'peer sr-only']) }} />

        {{-- Rádio Visual --}}
        <div
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border peer-checked:border-5
                    border-neutral-300
                    bg-white
                    transition-colors duration-200
                    peer-checked:border-neutral-900">
        </div>

        @if ($label)
            <span class="text-sm font-medium">
                {{ $label }}
            </span>
        @endif
    </label>
</div>

