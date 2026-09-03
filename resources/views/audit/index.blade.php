<x-layouts.app>
    <x-page-header
        title="Auditoria"
        description="Consulte as ações registradas no contexto institucional selecionado."
    />

    <div class="mt-6 space-y-6">
        <x-filter-bar
            :action="route('audit.index')"
            :filters="['area', 'event', 'causer', 'date_start', 'date_end', 'sort']"
            :show-search="false"
        >
            <div class="flex w-full flex-col divide-y divide-neutral-200 sm:w-auto sm:flex-row sm:divide-x sm:divide-y-0">
                <x-filter-bar.select name="area" aria-label="Área auditada">
                    <option value="">Todas as áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->area() }}" @selected(request('area') === $area->area())>
                            {{ $area->areaLabel() }}
                        </option>
                    @endforeach
                </x-filter-bar.select>

                <x-filter-bar.select name="event" aria-label="Evento auditado">
                    <option value="">Todos os eventos</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->value }}" @selected(request('event') === $event->value)>
                            {{ $event->label() }}
                        </option>
                    @endforeach
                </x-filter-bar.select>

                <x-filter-bar.select name="causer" aria-label="Responsável pela ação">
                    <option value="">Todos os responsáveis</option>
                    <option value="system" @selected(request('causer') === 'system')>Sistema</option>
                    @foreach ($causers as $causer)
                        <option value="{{ $causer->getKey() }}" @selected((string) request('causer') === (string) $causer->getKey())>
                            {{ $causer->name }}
                        </option>
                    @endforeach
                </x-filter-bar.select>

                <x-filter-bar.date-range
                    name-start="date_start"
                    :value-start="request('date_start')"
                    title-start="Data inicial"
                    name-end="date_end"
                    :value-end="request('date_end')"
                    title-end="Data final"
                />

                <x-filter-bar.select name="sort" aria-label="Ordenação">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Mais recentes</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Mais antigos</option>
                </x-filter-bar.select>
            </div>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[180px_minmax(0,1.2fr)_minmax(0,1.5fr)_200px] sm:grid">
                <x-table.column>DATA E HORA</x-table.column>
                <x-table.column>RESPONSÁVEL</x-table.column>
                <x-table.column>REGISTRO AFETADO</x-table.column>
                <x-table.column>AÇÃO</x-table.column>
            </x-table.header>

            <div class="divide-y divide-neutral-100">
                @forelse ($activities as $activity)
                    @php
                        $event = $activity->auditEvent();
                        $actorAffiliation = $activity->reference('actor_affiliation');
                    @endphp

                    <x-table.row :href="route('audit.show', $activity)" class="hidden grid-cols-[180px_minmax(0,1.2fr)_minmax(0,1.5fr)_200px] sm:grid">
                        <x-table.cell class="text-sm font-medium text-neutral-600">
                            {{ formatDateTime($activity->created_at) }}
                        </x-table.cell>

                        <x-table.cell>
                            @if ($activity->causer)
                                <div class="flex min-w-0 items-center gap-2">
                                    <x-avatar :model="$activity->causer" size="sm" />
                                    <div class="min-w-0">
                                        <span class="block truncate font-medium text-neutral-900">{{ $activity->causer->name }}</span>
                                        @if ($actorAffiliation)
                                            <span class="block truncate text-xs text-neutral-500">{{ $actorAffiliation['affiliation_type_label'] ?? 'Vínculo' }}</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-neutral-500">
                                    <x-heroicon-o-cpu-chip class="size-5" />
                                    <span class="font-medium">Sistema</span>
                                </div>
                            @endif
                        </x-table.cell>

                        <x-table.cell>
                            <p class="truncate font-medium text-neutral-900">{{ $activity->subjectLabel() }}</p>
                            <p class="mt-0.5 text-xs text-neutral-500">{{ $activity->auditSource()?->label() ?? 'Origem não informada' }}</p>
                        </x-table.cell>

                        <x-table.cell>
                            <x-badge :color="$event?->color() ?? 'neutral'" size="sm">
                                {{ $event?->label() ?? $activity->description }}
                            </x-badge>
                        </x-table.cell>

                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 space-y-2">
                                    <x-badge :color="$event?->color() ?? 'neutral'" size="sm">
                                        {{ $event?->label() ?? $activity->description }}
                                    </x-badge>
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ $activity->subjectLabel() }}</p>
                                    <p class="text-xs text-neutral-500">
                                        {{ $activity->causer?->name ?? 'Sistema' }}
                                        @if ($actorAffiliation)
                                            · {{ $actorAffiliation['affiliation_type_label'] ?? 'Vínculo' }}
                                        @endif
                                        · {{ $activity->auditSource()?->label() ?? 'Origem não informada' }}
                                    </p>
                                </div>
                                <time class="shrink-0 text-right text-xs font-medium text-neutral-500" datetime="{{ $activity->created_at->toIso8601String() }}">
                                    {{ formatDateTime($activity->created_at) }}
                                </time>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state
                        title="Nenhum registro encontrado"
                        description="Ainda não há ações registradas para os filtros e o contexto selecionados."
                        icon="heroicon-o-clipboard-document-list"
                    />
                @endforelse
            </div>
        </x-table>

        <div>
            {{ $activities->links() }}
        </div>
    </div>
</x-layouts.app>
