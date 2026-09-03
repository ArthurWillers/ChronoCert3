@props(['for' => null])

<label {{ $for ? 'for='.$for : '' }} {{ $attributes->merge(['class' => 'inline-flex items-center text-sm font-semibold text-neutral-700']) }}>
    {{ $slot }}
</label>

