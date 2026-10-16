@props(['name' => ''])

<select name="{{ $name }}"
        {{ $attributes->merge(['class' => 'min-w-0 w-full cursor-pointer rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5 text-sm text-neutral-700 transition-colors focus:border-accent focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/15 sm:w-auto sm:min-w-44 sm:flex-1 sm:py-2']) }}>
    {{ $slot }}
</select>
