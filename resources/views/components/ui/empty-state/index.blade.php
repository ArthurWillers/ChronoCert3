@props(['title' => null, 'description' => null, 'message' => null, 'icon' => null, 'actionText' => null, 'actionRoute' => null])

<div class="px-4 py-10 text-center">
    @if ($icon)
        <div class="mb-3 flex justify-center">
            <div class="flex size-11 items-center justify-center rounded-full bg-neutral-100">
                <x-dynamic-component :component="$icon" class="size-5 text-neutral-400" />
            </div>
        </div>
    @endif

    <div class="text-neutral-600">
        @if($title)
            <h3 class="text-base font-semibold text-neutral-900">{{ $title }}</h3>
        @endif
        @if($description || $message)
            <p class="{{ $title ? 'mt-1 text-sm text-neutral-500' : 'font-medium text-base' }}">
                {{ $description ?? $message }}
            </p>
        @endif
    </div>

    @if ($actionText && $actionRoute)
        <div class="mt-6">
            <x-button :href="$actionRoute">
                {{ $actionText }}
            </x-button>
        </div>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
