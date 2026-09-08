<x-layouts.app>
    <x-page-header title="Categorias de ACC"
        :description="'Categorias e regras de ' . $course->name . '.'"
        :action="$canCreate ? route('categories.create') : null"
        action-text="Nova categoria" icon="heroicon-o-plus" />

    @if ($course?->deactivated_at !== null)
        <x-callout color="yellow" class="mb-6">Este curso está inativo. Novas categorias e reativações ficam indisponíveis até a reativação do curso.</x-callout>
    @endif

    <div class="mt-6 space-y-6">
        <x-filter-bar :action="route('categories.index')" :filters="['search', 'status']" search-placeholder="Buscar categoria por nome">
            <x-filter-bar.select name="status" aria-label="Situação da categoria">
                <option value="">Todas as situações</option>
                <option value="active" @selected(request('status') === 'active')>Ativas</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativas</option>
            </x-filter-bar.select>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[minmax(0,1fr)_130px_130px] sm:grid">
                <x-table.column>CATEGORIA / CURSO</x-table.column>
                <x-table.column>LIMITE</x-table.column>
                <x-table.column align="right">SITUAÇÃO</x-table.column>
            </x-table.header>
            <div class="divide-y divide-neutral-100">
                @forelse ($categories as $category)
                    <x-table.row :href="route('categories.show', $category)" class="hidden grid-cols-[minmax(0,1fr)_130px_130px] sm:grid">
                        <x-table.cell>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-neutral-900">{{ $category->name }}</p>
                                <p class="mt-1 truncate text-sm text-neutral-500">{{ $category->course->name }}</p>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ number_format((float) $category->max_hours, 2, ',', '.') }} h</x-table.cell>
                        <x-table.cell align="right"><x-badge :color="$category->deactivated_at === null ? 'green' : 'red'" size="sm">{{ $category->deactivated_at === null ? 'Ativa' : 'Inativa' }}</x-badge></x-table.cell>
                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900">{{ $category->name }}</p>
                                    <p class="mt-1 text-sm text-neutral-500">{{ $category->course->name }}</p>
                                    <p class="mt-2 text-sm text-neutral-600">Até {{ number_format((float) $category->max_hours, 2, ',', '.') }} horas</p>
                                </div>
                                <x-badge :color="$category->deactivated_at === null ? 'green' : 'red'" size="sm">{{ $category->deactivated_at === null ? 'Ativa' : 'Inativa' }}</x-badge>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state title="Nenhuma categoria encontrada"
                        description="Não há categorias para os filtros selecionados."
                        icon="heroicon-o-tag" action-text="Nova categoria"
                        :action-route="$canCreate ? route('categories.create') : null" />
                @endforelse
            </div>
        </x-table>
        {{ $categories->links() }}
    </div>
</x-layouts.app>
