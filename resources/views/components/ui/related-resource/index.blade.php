@props([
    'icon',
    'title',
    'description',
    'url',
    'buttonText' => 'Visualizar'
])

<x-card {{ $attributes->merge(['class' => 'p-6 border-neutral-200 bg-neutral-50/50']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar :icon="$icon" size="lg" variant="white" radius="xl" />
            <div>
                <h3 class="font-bold text-neutral-900">{{ $title }}</h3>
                <p class="text-sm text-neutral-500 mt-0.5">
                    {{ $description }}
                </p>
            </div>
        </div>
        @if($url)
            <x-button href="{{ $url }}" class="t-learn shrink-0">
                {{ $buttonText }}
                <x-heroicon-m-arrow-right class="t-learn-chevron size-4 ml-1.5" />
            </x-button>
        @endif
    </div>
</x-card>

