<x-layouts.app>
    <div class="space-y-6">
        <x-page-header
            title="Categorias de ACC"
            :description="'Regras usadas nas análises de ' . $course->name . '.'"
            :action="$canCreate ? route('categories.create') : null"
            action-text="Nova categoria"
            icon="heroicon-o-plus"
        />

        @if ($course->deactivated_at !== null)
            <x-callout color="yellow">Este curso está inativo. Novas categorias e reativações ficam indisponíveis até a reativação do curso.</x-callout>
        @endif

        <x-filter-bar :action="route('categories.index')" :filters="['search', 'status']" search-placeholder="Buscar categoria por nome">
            <x-filter-bar.select name="status" aria-label="Situação da categoria">
                <option value="">Todas as situações</option>
                <option value="active" @selected(request('status') === 'active')>Ativas</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativas</option>
            </x-filter-bar.select>
        </x-filter-bar>

        <section aria-labelledby="category-list-title" class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="category-list-title" class="font-semibold text-neutral-900">{{ $course->name }}</h2>
                    <p class="mt-1 text-sm text-neutral-500">Categorias que orientam a classificação e o limite de horas dos certificados.</p>
                </div>
                <p class="shrink-0 text-sm font-medium text-neutral-600">
                    {{ $categories->total() }} {{ $categories->total() === 1 ? 'categoria' : 'categorias' }}
                </p>
            </div>

            <div class="divide-y divide-neutral-200">
                @forelse ($categories as $category)
                    @php($isActive = $category->deactivated_at === null)

                    <article class="group grid gap-x-8 gap-y-5 px-5 py-5 transition-colors duration-150 sm:px-6 lg:items-center {{ $canManageCategories ? 'lg:grid-cols-[minmax(14rem,1.1fr)_minmax(18rem,2fr)_10rem_auto]' : 'lg:grid-cols-[minmax(14rem,1.1fr)_minmax(18rem,2fr)_10rem]' }} {{ $isActive ? 'hover:bg-accent/[0.025]' : 'bg-neutral-50/70 hover:bg-neutral-100/70' }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold tracking-tight text-neutral-900">{{ $category->name }}</h3>
                                <x-badge :color="$isActive ? 'green' : 'red'" size="sm" rounded>
                                    {{ $isActive ? 'Ativa' : 'Inativa' }}
                                </x-badge>
                            </div>
                            @if (! $isActive)
                                <p class="mt-2 text-sm text-neutral-500">
                                    Inativada em {{ formatDateTime($category->deactivated_at) }}@if ($category->deactivation_reason): {{ $category->deactivation_reason }}@endif
                                </p>
                            @endif
                        </div>

                        <div class="border-l-2 border-accent/35 pl-4">
                            <p class="text-sm leading-6 text-neutral-600">
                                {{ $category->description ?: 'Sem descrição detalhada.' }}
                            </p>
                        </div>

                        <dl class="lg:text-right">
                            <dt class="text-sm font-medium text-neutral-500">Limite de aproveitamento</dt>
                            <dd class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">
                                {{ number_format((float) $category->max_hours, 2, ',', '.') }} <span class="text-sm font-medium text-neutral-500">horas</span>
                            </dd>
                        </dl>

                        @if ($canManageCategories)
                            <div class="flex flex-wrap items-center gap-2 lg:justify-end" aria-label="Ações para {{ $category->name }}">
                                @can('update', $category)
                                    <x-button :href="route('categories.edit', $category)" color="outline">
                                        <x-heroicon-o-pencil-square class="size-4" /> Editar
                                    </x-button>
                                @endcan
                                @can('deactivate', $category)
                                    <x-modal.trigger name="deactivate-category-{{ $category->id }}">
                                        <x-button color="danger-outline">Inativar</x-button>
                                    </x-modal.trigger>
                                @endcan
                                @can('reactivate', $category)
                                    <x-modal.restore
                                        :action="route('categories.reactivate', $category)"
                                        title="Reativar categoria"
                                        item-name="esta categoria"
                                        button-text="Reativar"
                                        confirm-text="Reativar"
                                        message="A categoria voltará a ficar disponível para novas análises."
                                    />
                                @endcan
                            </div>
                        @endif
                    </article>

                    @if ($canManageCategories)
                        @can('deactivate', $category)
                            <div class="contents" x-data x-init="@if ($errors->has('deactivation_reason')) $nextTick(() => $dispatch('modal-open', 'deactivate-category-{{ $category->id }}')) @endif">
                                <x-modal name="deactivate-category-{{ $category->id }}" title="Inativar categoria" confirm-variant="warning" hide-footer>
                                    <x-slot:content>
                                        <p>A categoria deixará de estar disponível para novas análises. O histórico será preservado.</p>
                                        <form method="POST" action="{{ route('categories.deactivate', $category) }}" class="mt-5 space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <x-form-textarea name="deactivation_reason" label="Motivo da inativação" required />
                                            <div class="flex justify-end gap-3">
                                                <x-button type="button" color="outline" @click="$dispatch('modal-close', 'deactivate-category-{{ $category->id }}')">Cancelar</x-button>
                                                <x-button type="submit" color="red">Inativar categoria</x-button>
                                            </div>
                                        </form>
                                    </x-slot:content>
                                </x-modal>
                            </div>
                        @endcan
                    @endif
                @empty
                    <x-empty-state
                        title="Nenhuma categoria encontrada"
                        description="Cadastre a primeira categoria para orientar as análises de ACC deste curso."
                        icon="heroicon-o-tag"
                        action-text="Nova categoria"
                        :action-route="$canCreate ? route('categories.create') : null"
                    />
                @endforelse
            </div>
        </section>

        {{ $categories->links() }}
    </div>
</x-layouts.app>
