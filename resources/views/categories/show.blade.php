<x-layouts.app>
    <x-page-header :title="$category->name" :description="$category->course->name">
        <x-back-button :fallback="route('categories.index')" />
        @can('update', $category)
            <x-button :href="route('categories.edit', $category)"><x-heroicon-o-pencil-square class="size-4" /> Editar</x-button>
        @endcan
    </x-page-header>

    @if ($errors->any())
        <x-callout color="red" class="mb-6">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-callout>
    @endif

    <div class="mt-6 grid max-w-5xl gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="space-y-6">
            @if ($category->course->deactivated_at !== null)
                <x-callout color="yellow">O curso está inativo. A edição e a reativação desta categoria ficam disponíveis após a reativação do curso.</x-callout>
            @endif
            <x-card>
                <h2 class="text-base font-semibold text-neutral-900">Regras acadêmicas</h2>
                @if ($category->description)
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ $category->description }}</p>
                @endif
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-neutral-500">Máximo de horas aproveitáveis</dt><dd class="font-semibold">{{ number_format((float) $category->max_hours, 2, ',', '.') }} h</dd></div>
                </dl>
            </x-card>
            <x-card>
                <h2 class="text-base font-semibold text-neutral-900">Documentos</h2>
                <p class="mt-3 text-sm leading-6 text-neutral-600">Documentos são opcionais. É permitido anexar múltiplos arquivos, com até 10 MB por arquivo.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (collect(config('acc.documents.accepted_file_types', []))->pluck('label') as $format)
                        <x-badge color="neutral">{{ $format }}</x-badge>
                    @endforeach
                </div>
            </x-card>
            <x-card>
                <h2 class="text-base font-semibold text-neutral-900">Orientação ao discente</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-neutral-600">{{ $category->guidance ?: 'Nenhuma orientação adicional cadastrada.' }}</p>
            </x-card>
        </div>
        <div class="space-y-6">
            <x-card>
                <h2 class="text-sm font-semibold text-neutral-900">Situação</h2>
                <div class="mt-3"><x-badge :color="$category->deactivated_at === null ? 'green' : 'red'">{{ $category->deactivated_at === null ? 'Ativa' : 'Inativa' }}</x-badge></div>
                @if ($category->deactivated_at !== null)
                    <p class="mt-3 text-sm text-neutral-500">Inativada em {{ formatDateTime($category->deactivated_at) }}.</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-neutral-600">{{ $category->deactivation_reason }}</p>
                @endif
                @can('deactivate', $category)
                    <x-modal.trigger name="deactivate-category-{{ $category->id }}">
                        <x-button color="danger-outline" class="mt-4 w-full"><x-heroicon-o-no-symbol class="size-4" /> Inativar categoria</x-button>
                    </x-modal.trigger>
                @endcan
                @can('reactivate', $category)
                    <x-modal.restore :action="route('categories.reactivate', $category)" title="Reativar categoria" item-name="esta categoria" button-text="Reativar categoria" confirm-text="Reativar" message="A categoria voltará a ficar disponível para novas análises." button-class="mt-4 w-full" />
                @endcan
            </x-card>
            <x-metadata-card :model="$category" />
            @can('delete', $category)
                <x-card :class="$canDelete ? 'border-red-200' : ''">
                    @if ($canDelete)
                        <h2 class="text-sm font-semibold text-neutral-900">Excluir categoria</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-500">Esta categoria ainda não possui registros de domínio relacionados.</p>
                        <x-modal.delete :action="route('categories.destroy', $category)" title="Excluir categoria" item-name="esta categoria" permanent button-text="Excluir categoria" button-class="mt-4 w-full" description="A categoria será removida. O registro de auditoria será preservado." />
                    @else
                        <h2 class="text-sm font-semibold text-neutral-900">Histórico preservado</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-500">Esta categoria possui registros relacionados e não pode ser excluída. Inative-a para impedir novas análises.</p>
                    @endif
                </x-card>
            @endcan
        </div>
    </div>
    @can('deactivate', $category)
        <div x-data x-init="@if ($errors->has('deactivation_reason')) $nextTick(() => $dispatch('modal-open', 'deactivate-category-{{ $category->id }}')) @endif">
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
</x-layouts.app>
