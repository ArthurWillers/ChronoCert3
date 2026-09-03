@props([
    'name' => '',
    'checked' => false,
    'value' => '1',
    'label' => '',
    'color' => 'neutral',
])

@php
    $switchColor = $color === 'accent' ? 'accent' : 'neutral';
    $focusRingClass = $switchColor === 'accent' ? 'focus:ring-accent' : 'focus:ring-neutral-900';
    $buttonClass = trim("t-toggle shrink-0 focus:outline-none focus:ring-2 {$focusRingClass} focus:ring-offset-2 ".($attributes->get('class') ?? ''));
    $buttonAttributes = $attributes->except(['x-model', 'class']);
@endphp

<div class="flex items-center gap-3" x-data="{
    checked: {{ $attributes->has('x-model') ? $attributes->get('x-model') : ($checked ? 'true' : 'false') }},
    toggle() {
        this.$refs.switch.classList.add('is-init');
        this.checked = !this.checked;
    },
    init() {
        if (this.$el.hasAttribute('x-model')) {
            this.$watch('checked', value => this.$dispatch('input', value));
        }
    }
}" {{ $attributes->whereStartsWith('x-model') }}>
    <button
        type="button"
        role="switch"
        class="{{ $buttonClass }}"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        data-on="{{ $checked ? 'true' : 'false' }}"
        x-ref="switch"
        :aria-checked="checked ? 'true' : 'false'"
        :data-on="checked ? 'true' : 'false'"
        data-color="{{ $switchColor }}"
        @click="toggle()"
        {{ $buttonAttributes }}
    >
        <span aria-hidden="true" class="t-toggle-thumb pointer-events-none"></span>
    </button>
    <input type="hidden" name="{{ $name }}" :value="checked ? '{{ $value }}' : '0'">
    @if($label)
        <span class="cursor-pointer text-sm font-medium text-neutral-700" @click="toggle()">{{ $label }}</span>
    @endif
</div>

