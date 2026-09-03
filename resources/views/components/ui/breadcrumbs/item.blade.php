@props([
    'href' => null,
])

<li class="flex items-center group/breadcrumb">
    @if ($href)
        <a href="{{ $href }}" {{ $attributes->merge(['class' => 'text-sm font-medium text-neutral-500 hover:text-neutral-900 transition-colors']) }}>
            {{ $slot }}
        </a>
    @else
        <span {{ $attributes->merge(['class' => 'text-sm font-semibold text-neutral-800']) }}>
            {{ $slot }}
        </span>
    @endif

    <x-heroicon-m-chevron-right class="size-4 mx-2 text-neutral-300 group-last/breadcrumb:hidden" />
</li>

