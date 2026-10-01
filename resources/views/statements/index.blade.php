@php
    $requiredHours = $mode === 'student' ? (float) ($affiliation->course?->required_acc_hours ?? 0) : 0;
    $pendingHours = $mode === 'student' ? max(0, $requiredHours - $recognizedHours) : 0;
@endphp

<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            :title="$mode === 'student' ? 'Seu progresso em ACC' : 'Acompanhamento de ACC'"
            :description="$mode === 'student'
                ? 'Veja o que já conta para sua carga horária e onde cada documento foi aplicado.'
                : 'Acompanhe as horas reconhecidas dos discentes do curso ativo.'"
            icon="heroicon-o-chart-bar-square"
        />

        @if ($mode === 'student')
            <x-card>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Horas nas categorias</h2>
                        <p class="mt-1 max-w-xl text-sm leading-6 text-neutral-600">
                            São as horas que contam para a ACC depois de aplicar o limite de cada categoria.
                        </p>
                    </div>
                    <p class="shrink-0 text-3xl font-semibold tracking-tight text-neutral-900">{{ number_format((float) $recognizedHours, 2, ',', '.') }} h</p>
                </div>

                @if ($requiredHours > 0)
                    <div class="mt-6 border-t border-neutral-100 pt-5">
                        <x-progress :value="$recognizedHours" :max="$requiredHours" label="Carga horária obrigatória" />
                        <p class="mt-2 text-sm text-neutral-500">
                            {{ number_format($recognizedHours, 2, ',', '.') }} h de {{ number_format($requiredHours, 2, ',', '.') }} h exigidas
                            @if ($pendingHours > 0)
                                · faltam {{ number_format($pendingHours, 2, ',', '.') }} h.
                            @else
                                · requisito cumprido.
                            @endif
                        </p>
                    </div>
                @endif

                <dl class="mt-6 grid gap-4 border-t border-neutral-100 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-neutral-500">Carga dos certificados aceitos</dt>
                        <dd class="mt-1 text-lg font-semibold text-neutral-900">{{ number_format((float) $totalCertificateHours, 2, ',', '.') }} h</dd>
                    </div>
                    @if ($minimumAreaHours !== null)
                        <div>
                            <dt class="text-sm text-neutral-500">Horas na área de formação</dt>
                            <dd class="mt-1 text-lg font-semibold text-neutral-900">{{ number_format((float) $recognizedAreaHours, 2, ',', '.') }} h <span class="text-sm font-medium text-neutral-500">de {{ number_format($minimumAreaHours, 2, ',', '.') }} h</span></dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-callout color="neutral" title="Como ler este resumo">
                A carga horária anotada no certificado é mantida no documento. Quando uma categoria atinge o próprio limite, somente o excedente deixa de contar naquela categoria.
            </x-callout>

            <x-card class="!p-0">
                <div class="border-b border-neutral-100 px-5 py-4">
                    <h2 class="font-semibold text-neutral-900">Categorias</h2>
                    <p class="mt-1 text-sm text-neutral-500">Confira o aproveitamento de cada grupo de atividades.</p>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse ($categorySummaries as $summary)
                        <div class="p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-semibold text-neutral-900">{{ $summary['category']->name }}</h3>
                                    <p class="mt-1 text-sm text-neutral-500">{{ $summary['accepted_documents_count'] }} {{ $summary['accepted_documents_count'] === 1 ? 'documento aceito' : 'documentos aceitos' }}</p>
                                </div>
                                <p class="text-sm font-semibold text-neutral-800">{{ number_format($summary['recognized_hours'], 2, ',', '.') }} h <span class="font-normal text-neutral-500">de {{ number_format((float) $summary['category']->max_hours, 2, ',', '.') }} h</span></p>
                            </div>
                            <x-progress class="mt-4" :value="$summary['recognized_hours']" :max="$summary['category']->max_hours" :show-value="false" />
                            <p class="mt-3 text-sm text-neutral-600">
                                Certificados aceitos: {{ number_format($summary['certificate_hours'], 2, ',', '.') }} h.
                            </p>
                        </div>
                    @empty
                        <x-empty-state title="Nenhuma categoria cadastrada" description="O curso ainda não possui categorias de ACC." icon="heroicon-o-tag" />
                    @endforelse
                </div>
            </x-card>

            <x-card class="!p-0">
                <div class="border-b border-neutral-100 px-5 py-4">
                    <h2 class="font-semibold text-neutral-900">Documentos</h2>
                    <p class="mt-1 text-sm text-neutral-500">Acompanhe o resultado de cada envio.</p>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse ($history as $submission)
                        <a href="{{ route('submissions.show', $submission) }}" class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-neutral-50 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-neutral-900 group-hover:text-accent">{{ $submission->review?->normalized_title ?? $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Documento' }}</p>
                                <p class="mt-1 text-sm text-neutral-500">
                                    {{ data_get($submission->review?->category_snapshot, 'name', $submission->review?->category?->name ?? 'Aguardando classificação') }}
                                    · enviado em {{ $submission->submitted_at->format('d/m/Y') }}
                                </p>
                                @if ($submission->review?->rejection_reason)
                                    <p class="mt-1 text-sm text-red-600">Motivo: {{ $submission->review->rejection_reason }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                @if ($submission->status === \App\Enums\SubmissionStatus::Approved)
                                    <span class="text-sm font-semibold text-neutral-900">{{ number_format((float) $submission->review->certificate_hours, 2, ',', '.') }} h</span>
                                @endif
                                <x-badge :color="match ($submission->status) {
                                    \App\Enums\SubmissionStatus::Submitted => 'yellow',
                                    \App\Enums\SubmissionStatus::UnderReview => 'blue',
                                    \App\Enums\SubmissionStatus::Rejected => 'red',
                                    \App\Enums\SubmissionStatus::Approved => 'green',
                                }">{{ $submission->status->label() }}</x-badge>
                                <x-heroicon-o-chevron-right class="size-4 text-neutral-400 group-hover:text-accent" />
                            </div>
                        </a>
                    @empty
                        <x-empty-state title="Nenhum documento enviado" description="Quando você enviar um documento, ele aparecerá aqui." icon="heroicon-o-document-text" />
                    @endforelse
                </div>
            </x-card>
        @else
            <x-card class="!p-0">
                <div class="flex flex-col gap-3 border-b border-neutral-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-neutral-900">Discentes do curso</h2>
                        <p class="mt-1 text-sm text-neutral-500">Horas já reconhecidas dentro dos limites de cada categoria.</p>
                    </div>
                    <p class="text-lg font-semibold text-neutral-900">{{ number_format((float) $totalCertificateHours, 2, ',', '.') }} h no total</p>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse ($students as $studentSummary)
                        <a href="{{ route('users.show', $studentSummary['affiliation']->user) }}" class="group flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-neutral-50">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-neutral-900 group-hover:text-accent">{{ $studentSummary['affiliation']->user->name }}</p>
                                <p class="mt-1 text-sm text-neutral-500">Matrícula {{ $studentSummary['affiliation']->registration_number }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <p class="font-semibold text-neutral-900">{{ number_format($studentSummary['certificate_hours'], 2, ',', '.') }} h</p>
                                <x-heroicon-o-chevron-right class="size-4 text-neutral-400 group-hover:text-accent" />
                            </div>
                        </a>
                    @empty
                        <x-empty-state title="Nenhum discente ativo" description="Não há vínculos discentes ativos neste curso." icon="heroicon-o-users" />
                    @endforelse
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.app>
