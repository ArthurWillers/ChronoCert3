@props(['name' => ''])

<select name="{{ $name }}" 
        {{ $attributes->merge(['class' => 'w-full sm:w-auto bg-transparent border-0 py-2 sm:py-1.5 pl-3 pr-8 text-sm text-neutral-600 focus:outline-none focus:ring-0 focus:bg-neutral-100 rounded-md cursor-pointer transition-colors']) }}>
    {{ $slot }}
</select>

