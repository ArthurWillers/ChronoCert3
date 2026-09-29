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
            title="Documento de ACC"
            :description="'Enviado em '.$submission->submitted_at->format('d/m/Y H:i').' · origem '.$submission->origin->label()"
        >
            <x-back-button :fallback="$isCoordinator
                ? route('submissions.students.index', $submission->studentAffiliation)
                : route('submissions.index')" />
        </x-page-header>

        <x-card>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-neutral-900">{{ $media?->getCustomProperty('original_filename') ?? 'Documento sem arquivo' }}</p>
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

                <x-ui.lightbox class="mt-6 flex flex-wrap gap-3">
                    @if (str_starts_with($media->mime_type, 'image/'))
                        <button
                            type="button"
                            @click="openLightbox(@js(route('submissions.document', $submission)), @js(route('submissions.download', $submission)), @js($media->getCustomProperty('original_filename', $media->file_name)))"
                            class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50"
                        >
                            <x-heroicon-o-eye class="size-5" /> Visualizar imagem
                        </button>
                    @else
                        <a href="{{ route('submissions.document', $submission) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-50">
                            <x-heroicon-o-eye class="size-5" /> Visualizar PDF
                        </a>
                    @endif
                    <a href="{{ route('submissions.download', $submission) }}" class="inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white transition hover:bg-accent/90">
                        <x-heroicon-o-arrow-down-tray class="size-5" /> Baixar arquivo
                    </a>
                </x-ui.lightbox>
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
                <p class="mt-2 text-sm leading-6 text-neutral-600">Inicie a análise para classificar a atividade, registrar a carga horária do certificado e decidir sobre o documento.</p>
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
                    <form
                        method="POST"
                        action="{{ route('reviews.complete', $review) }}"
                        class="mt-6 space-y-5"
                        aria-label="Decisão acadêmica do documento"
                        x-data="{ decision: @js(old('decision', 'approve')) }"
                    >
                        @csrf
                        <input type="hidden" name="decision" x-model="decision" />

                        <div>
                            <h3 class="font-semibold text-neutral-900">Dados para aceitação</h3>
                            <p class="mt-1 text-sm leading-6 text-neutral-600">Ao aceitar, a classificação e a decisão são concluídas juntas.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-form-input name="original_title" label="Título identificado no documento" :value="$review->original_title" x-bind:required="decision === 'approve'" maxlength="255" />
                            <x-form-input name="normalized_title" label="Título acadêmico corrigido" :value="$review->normalized_title" x-bind:required="decision === 'approve'" maxlength="255" />
                            <x-form-input name="certificate_hours" label="Carga horária do certificado" :value="$review->certificate_hours" x-bind:required="decision === 'approve'" inputmode="decimal" help="A carga horária declarada será aceita integralmente." />
                        </div>

                        <x-form-checkbox
                            name="is_area_related"
                            value="1"
                            :checked="old('is_area_related', $review->is_area_related)"
                            x-bind:required="decision === 'approve'"
                        >
                            Atividade relacionada à área de formação do curso
                        </x-form-checkbox>

                        <x-form-select name="acc_category_id" label="Categoria aplicada" x-bind:required="decision === 'approve'">
                            <option value="">Selecione uma categoria</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->getKey() }}" @selected((string) old('acc_category_id', $review->acc_category_id) === (string) $category->getKey())>
                                    {{ $category->name }} · limite {{ number_format((float) $category->max_hours, 2, ',', '.') }} h
                                </option>
                            @endforeach
                        </x-form-select>

                        <div class="border-t border-neutral-200 pt-5">
                            <x-form-textarea
                                name="rejection_reason"
                                label="Motivo da rejeição"
                                help="Preencha apenas se for rejeitar o documento."
                                x-bind:required="decision === 'reject'"
                                maxlength="2000"
                            />
                            <div class="mt-5 flex flex-wrap justify-end gap-3">
                                <x-button type="submit" color="danger-outline" @click="decision = 'reject'">
                                    <x-heroicon-o-x-circle /> Rejeitar documento
                                </x-button>
                                <x-button type="submit" variant="primary" @click="decision = 'approve'">
                                    <x-heroicon-o-check-circle /> Aceitar documento
                                </x-button>
                            </div>
                        </div>
                    </form>
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
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Carga horária do certificado</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->certificate_hours !== null ? number_format((float) $review->certificate_hours, 2, ',', '.').' h' : 'Não informada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Atividade na área</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->is_area_related ? 'Sim' : 'Não' }}</dd>
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
                        <p class="mt-5 text-sm text-neutral-600">Este documento ficará disponível até {{ $submission->purge_at->format('d/m/Y H:i') }}.</p>
                    @endif
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.app>
