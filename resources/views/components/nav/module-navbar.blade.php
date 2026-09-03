{{--
    Module topbar navbar — use directly inside any view that needs sub-navigation.

    Usage:
        <x-module-navbar>
            <x-module-nav-link :href="route('example')" :current="request()->routeIs('example')">
                Item
            </x-module-nav-link>
        </x-module-navbar>

    Props:
        scrollable (bool) — allows horizontal scroll when there are many items
--}}

@props(['scrollable' => false])

<div {{ $attributes->class('-mx-3 sm:-mx-6 lg:-mx-8 px-3 sm:px-6 lg:px-8 mb-6 border-b border-neutral-300 bg-neutral-100 -mt-3 sm:-mt-6 lg:-mt-8') }}>
    <nav @class([
        'flex items-center gap-0.5 py-3',
        'overflow-x-auto overflow-y-hidden' => $scrollable,
    ])>
        {{ $slot }}
    </nav>
</div>
