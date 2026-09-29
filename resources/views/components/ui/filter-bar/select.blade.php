@props(['name' => ''])

<select name="{{ $name }}"
        {{ $attributes->merge(['class' => 'min-w-0 w-full flex-1 cursor-pointer rounded-md border-0 bg-transparent py-2 pl-3 pr-8 text-sm text-neutral-600 transition-colors focus:bg-neutral-100 focus:outline-none focus:ring-0 sm:min-w-[11rem] sm:w-auto sm:py-1.5']) }}>
    {{ $slot }}
</select>
