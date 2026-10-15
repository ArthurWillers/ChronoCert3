<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header title="Fila de análises" description="Consulte os documentos do curso ativo e retome as análises em andamento." icon="heroicon-o-clipboard-document-check" />

        <x-filter-bar :action="route('submissions.queue')" :filters="['search', 'status', 'sort']" search-placeholder="Buscar por discente ou matrícula">
            <x-filter-bar.select name="status" aria-label="Situação">
                <option value="pending" @selected($filters['status'] === 'pending')>Aguardando análise ou decisão</option>
                <option value="all" @selected($filters['status'] === 'all')>Todas as situações</option>
                @foreach (\App\Enums\SubmissionStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-filter-bar.select>
            <x-filter-bar.select name="sort" aria-label="Ordenação">
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Mais antigos primeiro</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Mais recentes primeiro</option>
            </x-filter-bar.select>
        </x-filter-bar>

        <p class="text-sm text-neutral-600">{{ $submissions->total() }} {{ $submissions->total() === 1 ? 'documento encontrado' : 'documentos encontrados' }}</p>
        <x-card class="!p-0">
            <div class="divide-y divide-neutral-100">
                @forelse ($submissions as $submission)
                    <a href="{{ route('submissions.show', $submission) }}" class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:bg-neutral-50">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-neutral-900">{{ $submission->review?->normalized_title ?? $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Documento' }}</p>
                            <p class="mt-1 text-sm text-neutral-600">{{ $submission->studentAffiliation->user->name }} · Matrícula {{ $submission->studentAffiliation->registration_number }}</p>
                            <p class="mt-1 text-xs text-neutral-500">Enviado em {{ formatDateTime($submission->submitted_at) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :color="match ($submission->status) {
                                \App\Enums\SubmissionStatus::Submitted => 'yellow',
                                \App\Enums\SubmissionStatus::UnderReview => 'blue',
                                \App\Enums\SubmissionStatus::Approved => 'green',
                                \App\Enums\SubmissionStatus::Rejected => 'red',
                            }">{{ $submission->status->label() }}</x-badge>
                            <x-heroicon-o-chevron-right class="size-4 text-neutral-400" />
                        </div>
                    </a>
                @empty
                    <x-empty-state title="Nenhum documento encontrado" description="Ajuste os filtros para consultar outros documentos do curso." icon="heroicon-o-clipboard-document-check" />
                @endforelse
            </div>
        </x-card>
        {{ $submissions->links() }}
    </div>
</x-layouts.app>
