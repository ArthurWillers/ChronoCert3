@props([
    'href'    => '#',
    'current' => false,
])

{{--
    The nav parent has `py-3`, so items are `h-8` (32px) and the total nav height = 32+24 = 56px (min-h-14).
    The active indicator uses `after:-bottom-3` which places the 2px bar exactly at the border-b of the nav.
--}}
<a
    href="{{ $href }}"
    @if ($current) aria-current="page" @endif
    {{ $attributes->class([
        // Base
        'relative px-3 min-h-11 lg:min-h-8 inline-flex items-center gap-2 rounded-lg text-base lg:text-sm [&>svg]:size-5 lg:[&>svg]:size-4 font-medium whitespace-nowrap transition-colors duration-150',
        // Active indicator — 2px line sitting exactly on the nav border-b
        'after:absolute after:-bottom-3 after:inset-x-0 after:h-[2px] after:rounded-full',
        // Active
        'text-(--color-accent) after:bg-(--color-accent) hover:bg-neutral-800/5' => $current,
        // Inactive
        'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-800/5 after:bg-transparent' => ! $current,
    ]) }}
>
    {{ $slot }}
</a>

