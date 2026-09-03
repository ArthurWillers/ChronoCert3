@props([
    'label' => '',
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'options' => [],
])

@php
    $baseClasses =
        'h-11 w-full border appearance-none text-sm rounded-xl block py-2.5 px-4 bg-white disabled:shadow-none shadow-xs focus:shadow-lg text-neutral-700 disabled:text-neutral-400 placeholder-neutral-400 disabled:placeholder-neutral-400/70 outline-none focus:border-accent focus:ring-2 focus:ring-accent/40 transition-colors duration-300';
    $errorClasses = $errors->has($name)
        ? 'border-red-500 focus:border-red-500 focus:ring-red-400/30'
        : 'border-neutral-200';
    $classes = $baseClasses . ' ' . $errorClasses;
@endphp

<div x-data="{
    open: false,
    search: '{{ $value }}',
    options: {{ Js::from($options) }},
    focusedIndex: -1,
    init() {
        this.$watch('search', () => {
            this.focusedIndex = -1;
            if (!this.open && this.$el.contains(document.activeElement)) {
                this.open = true;
            }
        });
    },
    get filteredOptions() {
        if (this.search === '') return this.options;
        return this.options.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
    },
    selectOption(option) {
        this.search = option;
        this.open = false;
        this.focusedIndex = -1;
    },
    onArrowDown() {
        if (!this.open) {
            this.open = true;
            return;
        }
        if (this.focusedIndex < this.filteredOptions.length - 1) {
            this.focusedIndex++;
            this.scrollToFocused();
        }
    },
    onArrowUp() {
        if (this.focusedIndex > 0) {
            this.focusedIndex--;
            this.scrollToFocused();
        }
    },
    onEnter(e) {
        if (this.open) {
            e.preventDefault();
            if (this.focusedIndex >= 0 && this.focusedIndex < this.filteredOptions.length) {
                this.selectOption(this.filteredOptions[this.focusedIndex]);
            } else {
                this.open = false;
            }
        }
    },
    scrollToFocused() {
        this.$nextTick(() => {
            const el = this.$refs.listbox?.children[this.focusedIndex];
            if (el) {
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    }
}" 
class="relative"
@keydown.arrow-down.prevent="onArrowDown" 
@keydown.arrow-up.prevent="onArrowUp" 
@keydown.enter="onEnter" 
@keydown.escape="open = false; focusedIndex = -1">
    <div class="grid w-full items-center gap-1.5">
        @if($label)
            <label for="{{ $name }}" class="inline-flex items-center text-sm font-semibold text-neutral-700">{{ $label }}</label>
        @endif
        <input 
            type="text" 
            id="{{ $name }}" 
            name="{{ $name }}" 
            x-model="search"
            @focus="open = true"
            @click.away="open = false"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            class="{{ $classes }}"
        >
        <x-form-error name="{{ $name }}" />
    </div>
    
    <ul x-show="open && filteredOptions.length > 0" 
        x-transition
        x-cloak
        x-ref="listbox"
        class="absolute z-10 w-full bg-white border border-neutral-200 rounded-xl mt-1 max-h-60 overflow-auto shadow-lg">
        <template x-for="(option, index) in filteredOptions" :key="option">
            <li @click="selectOption(option)" 
                @mouseenter="focusedIndex = index"
                :class="{'bg-neutral-100 font-semibold text-accent': focusedIndex === index, 'text-neutral-700': focusedIndex !== index}"
                class="px-4 py-2 cursor-pointer text-sm transition-colors"
                x-text="option"></li>
        </template>
    </ul>
</div>

