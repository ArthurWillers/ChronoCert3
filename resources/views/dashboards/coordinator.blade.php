<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            title="Visão da coordenação"
            description="Comece pelas pendências do curso e acompanhe a operação em um só lugar."
            :action="route('users.index', ['type' => 'student'])"
            action-text="Ver discentes"
            icon="heroicon-o-document-text"
        />

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <x-card size="sm">
                <p class="text-sm text-neutral-600">Aguardando início</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $submissionCounts['submitted'] }}</p>
                <p class="mt-1 text-xs text-neutral-500">Documentos enviados</p>
            </x-card>
            <x-card size="sm">
                <p class="text-sm text-neutral-600">Em análise</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $submissionCounts['under_review'] }}</p>
                <p class="mt-1 text-xs text-neutral-500">Aguardando decisão</p>
            </x-card>
            <x-card href="{{ route('users.index', ['type' => 'student', 'status' => 'active']) }}" size="sm">
                <p class="text-sm text-neutral-600">Discentes ativos</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $activeStudentsCount }}</p>
                <p class="mt-1 text-xs text-neutral-500">No curso atual</p>
            </x-card>
            <x-card href="{{ route('categories.index') }}" size="sm">
                <p class="text-sm text-neutral-600">Categorias ativas</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $activeCategoriesCount }}</p>
                <p class="mt-1 text-xs text-neutral-500">Disponíveis para classificação</p>
            </x-card>
        </div>

        <x-card>
            <x-section-header title="Próximas análises" icon="heroicon-o-clipboard-document-list" />
            <div class="divide-y divide-neutral-100">
                @forelse ($pendingSubmissions as $submission)
                    @php
                        $documentName = $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Documento sem arquivo';
                    @endphp
                    <a href="{{ route('submissions.show', $submission) }}" class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:text-accent">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-neutral-900">{{ $documentName }}</p>
                            <p class="mt-1 truncate text-sm text-neutral-500">{{ $submission->studentAffiliation->user->name }} · {{ $submission->submitted_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <x-heroicon-o-chevron-right class="size-5 shrink-0 text-neutral-400" />
                    </a>
                @empty
                    <x-empty-state title="Nenhum documento aguardando início" description="Os novos documentos enviados pelos discentes aparecerão aqui." icon="heroicon-o-check-circle" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-layouts.app>
