@props([
    'label' => '',
    'name' => '',
    'id' => null,
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

@php
    $inputId = $id ?? ($bag === 'default' ? $name : $bag.'_'.$name);
    $descriptionIds = trim(($help ? $inputId.'-help ' : '').($errors->getBag($bag)->has($name) ? $inputId.'-error' : ''));
@endphp

<x-field>
    @if ($label)
        <x-label :for="$inputId" class="{{ $labelClass }}">
            {{ $label }}
        </x-label>
    @endif

    <x-input
        :name="$name"
        :id="$inputId"
        :type="$type"
        :value="old($name, $value)"
        :placeholder="$placeholder"
        :viewable="$viewable"
        :numeric="$numeric"
        :currency="$currency"
        :allow-negative="$allowNegative"
        :bag="$bag"
        :aria-describedby="$descriptionIds ?: null"
        :aria-invalid="$errors->getBag($bag)->has($name) ? 'true' : null"
        {{ $attributes }}
    />

    @if ($help)
        <p id="{{ $inputId }}-help" class="text-xs text-neutral-500">{{ $help }}</p>
    @endif

    <x-error :name="$name" :bag="$bag" :id="$inputId.'-error'" />
</x-field>
