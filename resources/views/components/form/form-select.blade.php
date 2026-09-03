@props([
    'label' => '',
    'name' => '',
    'labelClass' => '',
    'help' => null,
])

<x-field>
    @if ($label)
        <x-label :for="$name" class="{{ $labelClass }}">
            {{ $label }}
        </x-label>
    @endif

    <x-select :name="$name" {{ $attributes }}>
        {{ $slot }}
    </x-select>

    @if ($help)
        <p class="text-xs text-neutral-500">{{ $help }}</p>
    @endif

    <x-error :name="$name" />
</x-field>
