@props(['fallback', 'text' => 'Voltar', 'icon' => 'heroicon-o-arrow-left'])

<x-button
    color="outline"
    href="{{ $fallback }}"
    @click.prevent="
        const referrer = document.referrer;

        if (!referrer) {
            window.location.assign($el.href);
            return;
        }

        try {
            const previous = new URL(referrer);

            if (previous.origin !== window.location.origin || /\/(create|edit)(\/|$)/.test(previous.pathname)) {
                window.location.assign($el.href);
                return;
            }

            window.history.back();
        } catch {
            window.location.assign($el.href);
        }
    "
    {{ $attributes->merge(['class' => 'bg-white']) }}
>
    @if ($icon)
        <x-dynamic-component :component="$icon" class="size-4" />
    @endif
    <span>{{ $text }}</span>
</x-button>
