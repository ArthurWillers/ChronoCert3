@props(['name', 'label' => null, 'value' => null, 'help' => null, 'labelClass' => ''])

<x-field>
    @if($label)
        <x-label :for="$name" class="{{ $labelClass }}">{{ $label }}</x-label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'min-h-28 w-full rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm text-neutral-700 shadow-xs outline-none transition-colors duration-300 placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/40 focus:shadow-lg disabled:text-neutral-400']) }}>{{ old($name, $value) }}</textarea>
    @if($help)
        <p class="text-xs text-neutral-500">{{ $help }}</p>
    @endif
    <x-error :name="$name" />
</x-field>
