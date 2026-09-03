@props(['headerBg' => null, 'hasMultipleAffiliations' => false])

<div x-data="{ open: false }">
    <aside
        class="fixed top-0 left-0 z-40 flex h-screen w-64 -translate-x-full flex-col gap-4 border-e border-neutral-300 bg-neutral-100 p-4 transition-transform motion-duration-fast motion-ease-smooth-out lg:translate-x-0"
        :class="{ '-translate-x-full': !open }"
        x-cloak
    >
        <div class="flex items-center">
            <a href="{{ route('dashboard') }}" aria-label="ChronoCert"><x-app-logo /></a>
            <button type="button" @click="open = false" class="ms-auto cursor-pointer rounded-md p-1 hover:bg-neutral-200 lg:hidden" aria-label="Fechar navegação">
                <x-heroicon-o-x-mark class="size-6" />
            </button>
        </div>

        <nav class="flex flex-1 flex-col space-y-0.5">
            <x-nav-link :href="route('dashboard')" :current="request()->routeIs('dashboard')">
                <x-heroicon-o-home /> Início
            </x-nav-link>
        </nav>

        <x-dropdown position="top" class="mt-auto hidden lg:block" accent content-class="w-full">
            <x-slot:trigger>
                <button class="group flex w-full cursor-pointer items-center rounded-lg p-1 hover:bg-neutral-800/5">
                    <x-avatar :model="auth()->user()" />
                    <span class="mx-2 truncate text-sm font-medium text-neutral-800/80 group-hover:text-neutral-800">{{ auth()->user()->name }}</span>
                    <span class="ms-auto text-neutral-800/80 group-hover:text-neutral-800">
                        <x-heroicon-m-chevron-down class="size-5 transition-transform duration-200 ease-out" x-bind:class="{ 'rotate-180': open }" />
                    </span>
                </button>
            </x-slot:trigger>
            <x-slot:content>
                <div class="flex items-center gap-2 p-2">
                    <x-avatar :model="auth()->user()" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-neutral-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <hr class="my-1 border-neutral-300">
                @if ($hasMultipleAffiliations)
                    <a href="{{ route('affiliations.select') }}" @click="closeMenu()" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-200">
                        <x-heroicon-o-arrows-right-left class="size-5" /> Trocar vínculo
                    </a>
                @endif
                <a href="{{ route('settings') }}" @click="closeMenu()" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-200">
                    <x-heroicon-o-cog-6-tooth class="size-5" /> Configurações
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" @click="closeMenu()" class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-red-500 hover:bg-neutral-200">
                        <x-heroicon-m-arrow-right-start-on-rectangle class="size-5" /> Sair
                    </button>
                </form>
            </x-slot:content>
        </x-dropdown>
    </aside>

    <div
        class="fixed inset-0 z-30 bg-black/10 lg:hidden"
        x-cloak
        x-show="open"
        x-transition:enter="transition-opacity motion-duration-fast motion-ease-smooth-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity motion-duration-quick motion-ease-smooth-out"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
    ></div>

    <header class="flex min-h-14 w-full items-center px-6 lg:hidden {{ $headerBg }}">
        <button type="button" class="cursor-pointer rounded-lg p-1 hover:bg-neutral-200" @click="open = true" aria-label="Abrir navegação">
            <x-heroicon-o-bars-3 class="size-6" />
        </button>
        <x-dropdown position="bottom-end" class="ms-auto" accent content-class="w-60">
            <x-slot:trigger>
                <button class="group flex w-full cursor-pointer items-center gap-2 rounded-lg p-1 hover:bg-neutral-800/5">
                    <x-avatar :model="auth()->user()" />
                    <span class="ms-auto text-neutral-800/80 group-hover:text-neutral-800">
                        <x-heroicon-m-chevron-up class="size-4 transition-transform duration-200 ease-out" x-bind:class="{ 'rotate-180': open }" />
                    </span>
                </button>
            </x-slot:trigger>
            <x-slot:content>
                <div class="flex items-center gap-2 p-2">
                    <x-avatar :model="auth()->user()" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-neutral-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <hr class="my-1 border-neutral-300">
                @if ($hasMultipleAffiliations)
                    <a href="{{ route('affiliations.select') }}" @click="closeMenu()" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-200">
                        <x-heroicon-o-arrows-right-left class="size-5" /> Trocar vínculo
                    </a>
                @endif
                <a href="{{ route('settings') }}" @click="closeMenu()" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-200">
                    <x-heroicon-o-cog-6-tooth class="size-5" /> Configurações
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" @click="closeMenu()" class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-2 text-left text-sm text-red-500 hover:bg-neutral-200">
                        <x-heroicon-m-arrow-right-start-on-rectangle class="size-5" /> Sair
                    </button>
                </form>
            </x-slot:content>
        </x-dropdown>
    </header>
</div>
