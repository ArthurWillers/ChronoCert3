@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'viewable' => false,
    'labelClass' => '',
    'numeric' => false,
    'currency' => false,
    'allowNegative' => false,
    'bag' => 'default',
    'help' => null,
])

<x-field>
    @if ($label)
        <x-label :for="$name" class="{{ $labelClass }}">
            {{ $label }}
        </x-label>
    @endif

    <x-input
        :name="$name"
        :type="$type"
        :value="old($name, $value)"
        :placeholder="$placeholder"
        :viewable="$viewable"
        :numeric="$numeric"
        :currency="$currency"
        :allow-negative="$allowNegative"
        :bag="$bag"
        {{ $attributes }}
    />

    @if ($help)
        <p class="text-xs text-neutral-500">{{ $help }}</p>
    @endif

    <x-error :name="$name" :bag="$bag" />
</x-field>
