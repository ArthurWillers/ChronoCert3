<x-layouts.app>
    <x-page-header title="Editar categoria" :description="$category->course->name">
        <x-back-button :fallback="route('categories.show', $category)" />
    </x-page-header>
    <x-card class="mt-6 max-w-2xl">
        <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <p class="text-sm leading-6 text-neutral-500">As alterações valem para análises futuras. Aprovações anteriores preservam os dados usados na decisão.</p>
            <x-category-form :category="$category" />
            <div class="flex items-center justify-end gap-3 border-t border-neutral-100 pt-5">
                <x-back-button :fallback="route('categories.show', $category)" text="Cancelar" />
                <x-button type="submit" color="accent"><x-heroicon-o-check class="size-4" /> Salvar alterações</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
