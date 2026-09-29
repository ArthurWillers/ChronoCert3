<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            title="Visão do discente"
            description="Acompanhe o andamento dos documentos e o progresso das suas ACC."
            :action="route('submissions.create')"
            action-text="Enviar documento"
            icon="heroicon-o-arrow-up-tray"
        />

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <x-card href="{{ route('submissions.index', ['status' => \App\Enums\SubmissionStatus::Submitted->value]) }}" size="sm">
                <p class="text-sm text-neutral-600">Aguardando análise</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $submissionCounts['submitted'] + $submissionCounts['under_review'] }}</p>
                <p class="mt-1 text-xs text-neutral-500">Enviados ou em análise</p>
            </x-card>
            <x-card href="{{ route('submissions.index', ['status' => \App\Enums\SubmissionStatus::Approved->value]) }}" size="sm">
                <p class="text-sm text-neutral-600">Documentos aceitos</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $summary['acceptedDocumentsCount'] }}</p>
                <p class="mt-1 text-xs text-neutral-500">Com decisão final</p>
            </x-card>
            <x-card href="{{ route('statements.index') }}" size="sm">
                <p class="text-sm text-neutral-600">Horas nas categorias</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ number_format($summary['recognizedHours'], 2, ',', '.') }} h</p>
                <p class="mt-1 text-xs text-neutral-500">Já limitadas por categoria</p>
            </x-card>
            <x-card href="{{ route('statements.index') }}" size="sm">
                <p class="text-sm text-neutral-600">Atividades na área</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ number_format($summary['recognizedAreaHours'], 2, ',', '.') }} h</p>
                <p class="mt-1 text-xs text-neutral-500">Considerando os limites</p>
            </x-card>
        </div>

        <x-card>
            <x-section-header title="Documentos recentes" icon="heroicon-o-clock" />
            <div class="divide-y divide-neutral-100">
                @forelse ($recentSubmissions as $submission)
                    @php
                        $documentName = $submission->review?->normalized_title
                            ?? $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename')
                            ?? 'Documento sem arquivo';
                        $categoryName = data_get($submission->review?->category_snapshot, 'name', $submission->review?->category?->name);
                        $badgeColor = match ($submission->status) {
                            \App\Enums\SubmissionStatus::Submitted => 'yellow',
                            \App\Enums\SubmissionStatus::UnderReview => 'blue',
                            \App\Enums\SubmissionStatus::Rejected => 'red',
                            \App\Enums\SubmissionStatus::Approved => 'green',
                        };
                    @endphp
                    <a href="{{ route('submissions.show', $submission) }}" class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0 hover:text-accent">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-neutral-900">{{ $documentName }}</p>
                            <p class="mt-1 truncate text-sm text-neutral-500">{{ $categoryName ?? 'Aguardando classificação' }} · {{ $submission->submitted_at->format('d/m/Y') }}</p>
                        </div>
                        <x-badge :color="$badgeColor" size="sm">{{ $submission->status->label() }}</x-badge>
                    </a>
                @empty
                    <x-empty-state title="Nenhum documento enviado" description="Envie um documento para iniciar seu histórico de ACC." icon="heroicon-o-document-text" action-text="Enviar documento" :action-route="route('submissions.create')" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-layouts.app>
