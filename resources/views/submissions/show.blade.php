@php
    $media = $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection);
    $statusColor = match ($submission->status) {
        \App\Enums\SubmissionStatus::Submitted => 'yellow',
        \App\Enums\SubmissionStatus::UnderReview => 'blue',
        \App\Enums\SubmissionStatus::Rejected => 'red',
        \App\Enums\SubmissionStatus::Approved => 'green',
    };
@endphp

<x-layouts.app>
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header
            title="Comprovante de ACC"
            :description="'Enviado em '.$submission->submitted_at->format('d/m/Y H:i').' · origem '.$submission->origin->label()"
        />

        <x-card>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-neutral-900">{{ $media?->getCustomProperty('original_filename') ?? 'Comprovante sem arquivo' }}</p>
                    <p class="mt-2 text-sm text-neutral-600">Beneficiário: {{ $submission->studentAffiliation->user->name }} · Matrícula {{ $submission->studentAffiliation->registration_number }}</p>
                    <p class="mt-1 text-sm text-neutral-600">Enviado por: {{ $submission->submittedByAffiliation->user->name }}</p>
                </div>
                <x-badge :color="$statusColor">{{ $submission->status->label() }}</x-badge>
            </div>

            @if ($media !== null)
                <dl class="mt-6 grid grid-cols-1 gap-4 border-t border-neutral-200 pt-6 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Formato</dt>
                        <dd class="mt-1 text-sm text-neutral-800">{{ $media->mime_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Tamanho</dt>
                        <dd class="mt-1 text-sm text-neutral-800">{{ number_format($media->size / 1024 / 1024, 2, ',', '.') }} MB</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Armazenamento</dt>
                        <dd class="mt-1 text-sm text-neutral-800">Privado</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('submissions.document', $submission) }}" class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50">
                        <x-heroicon-o-eye class="size-5" /> Visualizar arquivo
                    </a>
                    <a href="{{ route('submissions.download', $submission) }}" class="inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white transition hover:bg-accent/90">
                        <x-heroicon-o-arrow-down-tray class="size-5" /> Baixar arquivo
                    </a>
                </div>
            @endif
        </x-card>

        @if ($errors->any())
            <x-callout color="red" title="Não foi possível concluir a operação">
                Revise os campos indicados e tente novamente.
            </x-callout>
        @endif

        @if ($canStartReview)
            <x-card>
                <x-section-header title="Análise acadêmica" icon="heroicon-o-clipboard-document-check" />
                <p class="mt-2 text-sm leading-6 text-neutral-600">Inicie a análise para classificar a atividade, definir as horas aproveitáveis e registrar uma decisão.</p>
                <form method="POST" action="{{ route('submissions.review.store', $submission) }}" class="mt-5">
                    @csrf
                    <x-button type="submit" variant="primary">
                        <x-heroicon-o-play /> Iniciar análise
                    </x-button>
                </form>
            </x-card>
        @endif

        @if ($submission->review !== null)
            @php($review = $submission->review)
            <x-card>
                <x-section-header title="Análise acadêmica" icon="heroicon-o-clipboard-document-check" />

                @if ($canUpdateReview)
                    <form method="POST" action="{{ route('reviews.update', $review) }}" class="mt-6 space-y-5" aria-label="Classificação acadêmica do comprovante">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-form-input name="original_title" label="Título identificado no comprovante" :value="$review->original_title" required maxlength="255" />
                            <x-form-input name="normalized_title" label="Título acadêmico corrigido" :value="$review->normalized_title" required maxlength="255" />
                            <x-form-input name="original_hours" label="Horas informadas" :value="$review->original_hours" inputmode="decimal" help="Deixe em branco quando o documento não informar carga horária." />
                            <x-form-input name="approved_hours" label="Horas a aproveitar" :value="$review->approved_hours" required inputmode="decimal" />
                        </div>

                        <x-form-select name="acc_category_id" label="Categoria aplicada" required>
                            <option value="">Selecione uma categoria</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->getKey() }}" @selected((string) old('acc_category_id', $review->acc_category_id) === (string) $category->getKey())>
                                    {{ $category->name }} · limite {{ number_format((float) $category->max_hours, 2, ',', '.') }} h
                                </option>
                            @endforeach
                        </x-form-select>

                        <x-form-textarea
                            name="classification_justification"
                            label="Justificativa da classificação"
                            :value="$review->classification_justification"
                            help="Obrigatória quando o título ou as horas forem corrigidos."
                            maxlength="2000"
                        />

                        <x-button type="submit" variant="primary">
                            <x-heroicon-o-check /> Salvar classificação
                        </x-button>
                    </form>

                    <div class="mt-8 grid grid-cols-1 gap-6 border-t border-neutral-200 pt-6 lg:grid-cols-2">
                        <section aria-labelledby="approve-review-title">
                            <h3 id="approve-review-title" class="font-semibold text-neutral-900">Aprovar comprovante</h3>
                            <p class="mt-1 text-sm leading-6 text-neutral-600">Somente as horas informadas na classificação serão contabilizadas, respeitando o saldo da categoria.</p>
                            <form method="POST" action="{{ route('reviews.approve', $review) }}" class="mt-4">
                                @csrf
                                <x-button type="submit" variant="primary" :disabled="$review->acc_category_id === null || $review->approved_hours === null">
                                    <x-heroicon-o-check-circle /> Aprovar e contabilizar
                                </x-button>
                            </form>
                        </section>

                        <section aria-labelledby="reject-review-title">
                            <h3 id="reject-review-title" class="font-semibold text-neutral-900">Rejeitar comprovante</h3>
                            <form method="POST" action="{{ route('reviews.reject', $review) }}" class="mt-4 space-y-4">
                                @csrf
                                <x-form-textarea name="rejection_reason" label="Motivo da rejeição" required maxlength="2000" />
                                <x-button type="submit" variant="danger">
                                    <x-heroicon-o-x-circle /> Rejeitar comprovante
                                </x-button>
                            </form>
                        </section>
                    </div>
                @else
                    <dl class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Título acadêmico</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->normalized_title ?? $review->original_title }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Categoria aplicada</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ data_get($review->category_snapshot, 'name', $review->category?->name ?? 'Não aplicável') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Horas aprovadas</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->approved_hours !== null ? number_format((float) $review->approved_hours, 2, ',', '.').' h' : 'Não contabilizadas' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Responsável</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->reviewerAffiliation->user->name }}</dd>
                        </div>
                    </dl>

                    @if ($review->rejection_reason !== null)
                        <x-callout color="red" title="Motivo da rejeição" class="mt-6">
                            {{ $review->rejection_reason }}
                        </x-callout>
                    @endif

                    @if ($submission->status === \App\Enums\SubmissionStatus::Rejected && $submission->purge_at !== null)
                        <p class="mt-5 text-sm text-neutral-600">Este comprovante ficará disponível até {{ $submission->purge_at->format('d/m/Y H:i') }}.</p>
                    @endif
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.app>
