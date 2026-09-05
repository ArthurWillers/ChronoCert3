<x-layouts.app>
    @php
        $isCoordinator = $activeAffiliation->type === \App\Enums\AffiliationType::Coordinator;
    @endphp

    <x-page-header
        title="Usuários"
        :description="$isCoordinator ? 'Gerencie os discentes vinculados ao seu curso selecionado.' : 'Gerencie identidades institucionais e seus vínculos.'"
        :action="route('users.create')"
        action-text="Novo usuário"
        icon="heroicon-o-plus"
    >
        @if ($isCoordinator)
            <x-button :href="route('users.lookup')" color="outline">
                <x-heroicon-o-magnifying-glass class="size-4" /> Vincular usuário existente
            </x-button>
        @endif
    </x-page-header>

    <div class="mt-6 space-y-6">
        <x-filter-bar :action="route('users.index')" :filters="['search', 'course_id', 'type', 'status']" search-placeholder="Buscar por nome">
            <x-filter-bar.select name="course_id" aria-label="Curso">
                <option value="">Todos os cursos</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->name }}</option>
                @endforeach
            </x-filter-bar.select>
            <x-filter-bar.select name="type" aria-label="Tipo de vínculo">
                <option value="">Todos os tipos</option>
                @foreach ($affiliationTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </x-filter-bar.select>
            <x-filter-bar.select name="status" aria-label="Situação do vínculo">
                <option value="">Todas as situações</option>
                <option value="active" @selected(request('status') === 'active')>Ativos</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Desativados</option>
            </x-filter-bar.select>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[minmax(0,1.3fr)_minmax(0,1.4fr)_minmax(0,1.2fr)_150px] sm:grid">
                <x-table.column>USUÁRIO</x-table.column>
                <x-table.column>VÍNCULO</x-table.column>
                <x-table.column>CURSO / MATRÍCULA</x-table.column>
                <x-table.column align="right">SITUAÇÃO</x-table.column>
            </x-table.header>

            <div class="divide-y divide-neutral-100">
                @forelse ($users as $user)
                    @php
                        $visibleAffiliations = $isCoordinator
                            ? $user->affiliations->where('type', \App\Enums\AffiliationType::Student)->where('course_id', $activeAffiliation->course_id)
                            : $user->affiliations;
                        $primaryAffiliation = $visibleAffiliations->first();
                    @endphp
                    <x-table.row :href="route('users.show', $user)" class="hidden grid-cols-[minmax(0,1.3fr)_minmax(0,1.4fr)_minmax(0,1.2fr)_150px] sm:grid">
                        <x-table.cell>
                            <p class="truncate font-semibold text-neutral-900">{{ $user->name }}</p>
                            @if (! $isCoordinator)
                                <p class="mt-0.5 truncate text-xs text-neutral-500">{{ $user->email }}</p>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <p class="truncate text-sm font-medium text-neutral-800">{{ $primaryAffiliation?->type->label() ?? 'Sem vínculo' }}</p>
                            @if ($visibleAffiliations->count() > 1)
                                <p class="mt-0.5 text-xs text-neutral-500">{{ $visibleAffiliations->count() }} vínculos no escopo</p>
                            @endif
                        </x-table.cell>
                        <x-table.cell class="text-sm text-neutral-600">
                            <p class="truncate">{{ $primaryAffiliation?->course?->name ?? 'Atuação global' }}</p>
                            @if ($primaryAffiliation?->registration_number)
                                <p class="mt-0.5 text-xs text-neutral-500">Matrícula {{ $primaryAffiliation->registration_number }}</p>
                            @endif
                        </x-table.cell>
                        <x-table.cell align="right">
                            <x-badge :color="$primaryAffiliation?->isActive() ? 'green' : 'red'" size="sm">
                                {{ $primaryAffiliation?->isActive() ? 'Ativo' : 'Desativado' }}
                            </x-badge>
                        </x-table.cell>

                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900">{{ $user->name }}</p>
                                    <p class="mt-1 truncate text-sm text-neutral-600">{{ $primaryAffiliation?->type->label() ?? 'Sem vínculo' }} · {{ $primaryAffiliation?->course?->name ?? 'Atuação global' }}</p>
                                    @if ($primaryAffiliation?->registration_number)
                                        <p class="mt-1 text-xs text-neutral-500">Matrícula {{ $primaryAffiliation->registration_number }}</p>
                                    @endif
                                </div>
                                <x-badge :color="$primaryAffiliation?->isActive() ? 'green' : 'red'" size="sm">
                                    {{ $primaryAffiliation?->isActive() ? 'Ativo' : 'Desativado' }}
                                </x-badge>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state
                        title="Nenhum usuário encontrado"
                        :description="$isCoordinator ? 'Cadastre um discente novo ou localize um usuário existente pelo CPF para criar o vínculo deste curso.' : 'Cadastre o primeiro usuário institucional para começar.'"
                        icon="heroicon-o-users"
                        action-text="Novo usuário"
                        :action-route="route('users.create')"
                    />
                @endforelse
            </div>
        </x-table>

        {{ $users->links() }}
    </div>
</x-layouts.app>
