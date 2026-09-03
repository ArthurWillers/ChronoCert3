@props([
    'name',
    'title',
    'items' => [],
    'valuePlaceholder' => 'Valor',
    'emptyMessage' => 'Nenhum item cadastrado.',
])

<x-card {{ $attributes->merge(['class' => 'h-full p-6 flex flex-col']) }} x-data="{
    items: {{ Js::from($items) }},
    serverErrors: {{ Js::from($errors->messages()) }},
    addItem() {
        this.items.push({ label: '', value: '' });
    },
    removeItem(index) {
        this.items.splice(index, 1);
    }
}">
    <div class="flex justify-between items-center mb-6 shrink-0">
        <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-widest">{{ $title }}</h3>
        <x-button type="button" @click="addItem" color="accent-ghost" class="text-xs! py-1! px-2!">
            <x-heroicon-o-plus class="size-3" /> Adicionar
        </x-button>
    </div>
    
    <div>
        <div class="flex flex-col gap-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="flex gap-2 items-start">
                    <div class="w-1/3">
                        <x-form-input x-model="item.label" x-bind:name="`{{ $name }}[${index}][label]`" placeholder="Rótulo" x-bind:class="serverErrors[`{{ $name }}.${index}.label`] ? 'border-red-500! focus:border-red-500! focus:ring-red-400/30!' : ''" />
                        <template x-if="serverErrors[`{{ $name }}.${index}.label`]">
                            <div class="flex items-center gap-x-2 text-sm text-red-500 mt-1.5 animate-shake">
                                <x-heroicon-m-exclamation-triangle class="size-5 shrink-0" />
                                <span class="font-semibold wrap-break-words" x-text="serverErrors[`{{ $name }}.${index}.label`][0]"></span>
                            </div>
                        </template>
                    </div>
                    <div class="flex-1">
                        <x-form-input x-model="item.value" x-bind:name="`{{ $name }}[${index}][value]`" placeholder="{{ $valuePlaceholder }}" x-bind:class="serverErrors[`{{ $name }}.${index}.value`] ? 'border-red-500! focus:border-red-500! focus:ring-red-400/30!' : ''" />
                        <template x-if="serverErrors[`{{ $name }}.${index}.value`]">
                            <div class="flex items-center gap-x-2 text-sm text-red-500 mt-1.5 animate-shake">
                                <x-heroicon-m-exclamation-triangle class="size-5 shrink-0" />
                                <span class="font-semibold wrap-break-words" x-text="serverErrors[`{{ $name }}.${index}.value`][0]"></span>
                            </div>
                        </template>
                    </div>
                    <x-tooltip text="Remover item" class="shrink-0">
                        <x-button type="button" @click="removeItem(index)" color="danger-ghost" class="size-11! shrink-0 p-0!" aria-label="Remover item">
                            <x-heroicon-o-trash class="size-4" />
                        </x-button>
                    </x-tooltip>
                </div>
            </template>
            <p class="text-sm text-neutral-400 italic" x-show="items.length === 0">{{ $emptyMessage }}</p>
        </div>
    </div>
</x-card>
