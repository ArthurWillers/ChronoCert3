<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            title="Extrato de ACC"
            :description="$mode === 'student'
                ? 'Acompanhe as horas aprovadas no vínculo acadêmico ativo.'
                : 'Acompanhe a contabilização dos discentes do curso ativo.'"
            icon="heroicon-o-chart-bar-square"
        />

        <x-card>
            <p class="text-sm font-medium text-neutral-600">Total de horas aprovadas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ number_format((float) $totalApprovedHours, 2, ',', '.') }} h</p>
        </x-card>

        @if ($mode === 'student')
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($categorySummaries as $summary)
                    <x-card size="sm">
                        <h2 class="font-semibold text-neutral-900">{{ $summary['category']->name }}</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-neutral-500">Aprovadas</dt>
                                <dd class="mt-1 font-semibold text-neutral-900">{{ number_format($summary['approved_hours'], 2, ',', '.') }} h</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500">Disponíveis</dt>
                                <dd class="mt-1 font-semibold text-neutral-900">{{ number_format($summary['available_hours'], 2, ',', '.') }} h</dd>
                            </div>
                        </dl>
                    </x-card>
                @empty
                    <x-empty-state title="Nenhuma categoria cadastrada" description="O curso ainda não possui categorias de ACC." icon="heroicon-o-tag" />
                @endforelse
            </div>

            <x-card>
                <x-section-header title="Histórico de submissões" icon="heroicon-o-clock" />
                <div class="mt-5 divide-y divide-neutral-100">
                    @forelse ($history as $submission)
                        <a href="{{ route('submissions.show', $submission) }}" class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-neutral-900">{{ $submission->review?->normalized_title ?? $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Comprovante' }}</p>
                                <p class="mt-1 text-sm text-neutral-500">
                                    {{ data_get($submission->review?->category_snapshot, 'name', $submission->review?->category?->name ?? 'Sem categoria') }}
                                    · {{ $submission->submitted_at->format('d/m/Y') }}
                                </p>
                                @if ($submission->review?->rejection_reason)
                                    <p class="mt-1 text-sm text-red-600">Motivo: {{ $submission->review->rejection_reason }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                @if ($submission->status === \App\Enums\SubmissionStatus::Approved)
                                    <span class="font-semibold text-neutral-900">{{ number_format((float) $submission->review->approved_hours, 2, ',', '.') }} h</span>
                                @endif
                                <x-badge :color="match ($submission->status) {
                                    \App\Enums\SubmissionStatus::Submitted => 'yellow',
                                    \App\Enums\SubmissionStatus::UnderReview => 'blue',
                                    \App\Enums\SubmissionStatus::Rejected => 'red',
                                    \App\Enums\SubmissionStatus::Approved => 'green',
                                }">{{ $submission->status->label() }}</x-badge>
                            </div>
                        </a>
                    @empty
                        <x-empty-state title="Nenhuma submissão" description="Seu histórico de comprovantes aparecerá aqui." icon="heroicon-o-document-text" />
                    @endforelse
                </div>
            </x-card>
        @else
            <x-card>
                <x-section-header title="Discentes do curso" icon="heroicon-o-users" />
                <div class="mt-5 divide-y divide-neutral-100">
                    @forelse ($students as $studentSummary)
                        <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div>
                                <p class="font-semibold text-neutral-900">{{ $studentSummary['affiliation']->user->name }}</p>
                                <p class="mt-1 text-sm text-neutral-500">Matrícula {{ $studentSummary['affiliation']->registration_number }}</p>
                            </div>
                            <p class="font-semibold text-neutral-900">{{ number_format($studentSummary['approved_hours'], 2, ',', '.') }} h</p>
                        </div>
                    @empty
                        <x-empty-state title="Nenhum discente ativo" description="Não há vínculos discentes ativos neste curso." icon="heroicon-o-users" />
                    @endforelse
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.app>
