<x-layouts.app>
    <x-page-header
        title="Cursos"
        description="Gerencie os cursos disponíveis para vínculos e configurações acadêmicas."
        :action="route('courses.create')"
        action-text="Novo curso"
        icon="heroicon-o-plus"
    />

    <div class="mt-6 space-y-6">
        <x-filter-bar :action="route('courses.index')" :filters="['search', 'status']" search-placeholder="Buscar por nome">
            <x-filter-bar.select name="status" aria-label="Situação do curso">
                <option value="">Todas as situações</option>
                <option value="active" @selected(request('status') === 'active')>Ativos</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
            </x-filter-bar.select>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[minmax(0,1fr)_170px_130px] sm:grid">
                <x-table.column>CURSO</x-table.column>
                <x-table.column>VÍNCULOS</x-table.column>
                <x-table.column align="right">SITUAÇÃO</x-table.column>
            </x-table.header>

            <div class="divide-y divide-neutral-100">
                @forelse ($courses as $course)
                    <x-table.row :href="route('courses.edit', $course)" class="hidden grid-cols-[minmax(0,1fr)_170px_130px] sm:grid">
                        <x-table.cell>
                            <span class="truncate font-semibold text-neutral-900">{{ $course->name }}</span>
                        </x-table.cell>
                        <x-table.cell class="text-sm text-neutral-600">
                            {{ $course->affiliations_count }}
                        </x-table.cell>
                        <x-table.cell align="right">
                            <x-badge :color="$course->deactivated_at === null ? 'green' : 'red'" size="sm">
                                {{ $course->deactivated_at === null ? 'Ativo' : 'Inativo' }}
                            </x-badge>
                        </x-table.cell>

                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900">{{ $course->name }}</p>
                                    <p class="mt-1 text-sm text-neutral-500">{{ $course->affiliations_count }} vínculo(s)</p>
                                </div>
                                <x-badge :color="$course->deactivated_at === null ? 'green' : 'red'" size="sm">
                                    {{ $course->deactivated_at === null ? 'Ativo' : 'Inativo' }}
                                </x-badge>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state
                        title="Nenhum curso encontrado"
                        description="Cadastre o primeiro curso para começar a criar vínculos de coordenação e discentes."
                        icon="heroicon-o-academic-cap"
                        action-text="Novo curso"
                        :action-route="route('courses.create')"
                    />
                @endforelse
            </div>
        </x-table>

        {{ $courses->links() }}
    </div>
</x-layouts.app>
