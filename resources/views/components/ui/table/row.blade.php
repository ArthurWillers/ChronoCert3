@props(['mobile' => null, 'href' => null, 'mobileBreakpoint' => 'sm'])
@php
    $mobileHideClass = match($mobileBreakpoint) {
        'md' => 'md:hidden',
        'lg' => 'lg:hidden',
        'xl' => 'xl:hidden',
        '2xl' => '2xl:hidden',
        default => 'sm:hidden',
    };
@endphp
<div class="relative group/row last:rounded-b-xl first:rounded-t-xl border-b border-neutral-100 last:border-0">
    @if($href)
        <a href="{{ $href }}" {{ $attributes->merge(['class' => 'grid items-center hover:bg-neutral-100 transition-colors rounded-b-inherit rounded-t-inherit cursor-pointer']) }}>
            {{ $slot }}
        </a>
    @else
        <div {{ $attributes->merge(['class' => 'grid items-center hover:bg-neutral-100 transition-colors rounded-b-inherit rounded-t-inherit']) }}>
            {{ $slot }}
        </div>
    @endif

    @if($mobile)
        @if($href)
            <a href="{{ $href }}" class="{{ $mobileHideClass }} p-4 space-y-3 block rounded-b-inherit hover:bg-neutral-100 transition-colors cursor-pointer">
                {{ $mobile }}
            </a>
        @else
            <div class="{{ $mobileHideClass }} p-4 space-y-3 block rounded-b-inherit">
                {{ $mobile }}
            </div>
        @endif
    @endif
</div>

