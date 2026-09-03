@props(['title' => null, 'description' => null, 'message' => null, 'icon' => null, 'actionText' => null, 'actionRoute' => null])

<div class="py-12 px-4 text-center">
    @if ($icon)
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center">
                <x-dynamic-component :component="$icon" class="w-8 h-8 text-neutral-400" />
            </div>
        </div>
    @endif

    <div class="text-neutral-600">
        @if($title)
            <h3 class="text-lg font-medium text-neutral-900">{{ $title }}</h3>
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
