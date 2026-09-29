@props(['title', 'description' => null, 'action' => null, 'actionText' => null, 'icon' => null, 'mobileBottom' => false])

<div class="mb-5 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
    <div class="min-w-0">
        <h2 class="text-xl font-semibold text-neutral-900 sm:text-2xl">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm leading-5 text-neutral-500">{{ $description }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty() || $action)
        <div class="{{ $mobileBottom ? 'hidden md:flex' : 'flex' }} w-full flex-wrap items-center justify-start gap-2 sm:w-auto sm:justify-end">
            {{ $slot }}
            
            @if ($action)
                <x-button :href="$action" class="w-full sm:w-auto">
                    @if ($icon)
                        <x-dynamic-component :component="$icon" class="size-5!" />
                    @endif
                    <span class="whitespace-nowrap">{{ $actionText }}</span>
                </x-button>
            @endif
        </div>
    @endif
</div>
