<x-layouts.app>
    <x-page-header title="Nova categoria de ACC" :description="$course->name">
        <x-back-button :fallback="route('categories.index')" />
    </x-page-header>
    <x-card class="mt-6 max-w-2xl">
        <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
            @csrf
            <x-category-form />
            <div class="flex items-center justify-end gap-3 border-t border-neutral-100 pt-5">
                <x-back-button :fallback="route('categories.index')" text="Cancelar" />
                <x-button type="submit" color="accent"><x-heroicon-o-check class="size-4" /> Salvar categoria</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
