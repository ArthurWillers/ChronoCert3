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
    <div class="w-full space-y-6">
        <x-page-header
            title="Documento de ACC"
            :description="'Enviado em '.$submission->submitted_at->format('d/m/Y H:i').' · origem '.$submission->origin->label()"
        >
            <x-back-button :fallback="$isCoordinator
                ? route('submissions.queue')
                : route('submissions.index')" />
            @if ($nextSubmission !== null)
                <x-button :href="route('submissions.show', $nextSubmission)" color="accent">Próxima análise <x-heroicon-o-arrow-right /></x-button>
            @endif
        </x-page-header>

        <div class="grid items-start gap-6 {{ $isCoordinator ? 'lg:grid-cols-2' : '' }}">
        <x-card :class="$isCoordinator ? 'lg:sticky lg:top-24' : ''">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="break-words text-lg font-semibold text-neutral-900">{{ $media?->getCustomProperty('original_filename') ?? 'Documento sem arquivo' }}</p>
                    <p class="mt-2 text-sm text-neutral-600">Beneficiário: {{ $submission->studentAffiliation->user->name }} · Matrícula {{ $submission->studentAffiliation->registration_number }}</p>
                    <p class="mt-1 text-sm text-neutral-600">Enviado por: {{ $submission->submittedByAffiliation->user->name }}</p>
                </div>
                <x-badge :color="$statusColor">{{ $submission->status->label() }}</x-badge>
            </div>

            @if ($media !== null)
                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-neutral-200 pt-6">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Formato</dt>
                        <dd class="mt-1 text-sm text-neutral-800">{{ \App\Models\AccSubmission::acceptedFileTypes()[$media->mime_type]['label'] ?? 'Documento' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Tamanho</dt>
                        <dd class="mt-1 text-sm text-neutral-800">{{ $media->size < 1024 * 1024 ? number_format($media->size / 1024, 1, ',', '.').' KB' : number_format($media->size / 1024 / 1024, 2, ',', '.').' MB' }}</dd>
                    </div>
                </dl>

                @if ($isCoordinator)
                    <div class="mt-5 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-100">
                        @if (str_starts_with($media->mime_type, 'image/'))
                            <img src="{{ route('submissions.document', $submission) }}" alt="Comprovante de {{ $submission->studentAffiliation->user->name }}" class="max-h-[65vh] min-h-64 w-full object-scale-down" />
                        @else
                            <iframe src="{{ route('submissions.document', $submission) }}" title="Comprovante de ACC de {{ $submission->studentAffiliation->user->name }}" class="h-[65vh] min-h-96 w-full"></iframe>
                        @endif
                    </div>
                @endif

                <x-lightbox class="mt-6 flex flex-wrap gap-3">
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
                </x-lightbox>
            @endif
        </x-card>

        <div class="space-y-6">
        @if ($errors->any())
            <x-callout color="red" title="Não foi possível concluir a operação">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
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
                        x-data="accReviewDecision(@js($categoryImpact), @js(old('acc_category_id', $review->acc_category_id)), @js(old('certificate_hours', $review->certificate_hours)))"
                        @submit="if (loading) { $event.preventDefault(); return; } loading = true"
                    >
                        @csrf
                        <input type="hidden" name="decision" value="approve" />

                        <div>
                            <h3 class="font-semibold text-neutral-900">Dados para aceitação</h3>
                            <p class="mt-1 text-sm leading-6 text-neutral-600">Ao aceitar, a classificação e a decisão são concluídas juntas.</p>
                        </div>
                        <div class="space-y-5">
                            <x-form-input name="original_title" label="Título identificado no documento" :value="$review->original_title" required maxlength="255" />
                            <x-form-input name="normalized_title" label="Título acadêmico corrigido" :value="$review->normalized_title" required maxlength="255" />
                            <x-form-input name="certificate_hours" label="Carga horária do certificado" :value="$review->certificate_hours" type="text" inputmode="decimal" pattern="[0-9]+([.][0-9]{0,2})?" required @keydown="$event.key === 'e' || $event.key === 'E' || $event.key === '+' || $event.key === '-' ? $event.preventDefault() : null" @input="normalizeCertificateHours($event)" help="Digite apenas números; vírgulas são convertidas em ponto automaticamente." />
                        </div>

                        <x-form-checkbox
                            name="is_area_related"
                            value="1"
                            :checked="old('is_area_related', $review->is_area_related)"
                        >
                            Atividade relacionada à área de formação do curso
                        </x-form-checkbox>

                        <x-form-select name="acc_category_id" label="Categoria aplicada" required x-model="categoryId">
                            <option value="">Selecione uma categoria</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->getKey() }}" @selected((string) old('acc_category_id', $review->acc_category_id) === (string) $category->getKey())>
                                    {{ $category->name }} · limite {{ number_format((float) $category->max_hours, 2, ',', '.') }} h
                                </option>
                            @endforeach
                        </x-form-select>

                        <div x-show="category" x-cloak class="rounded-lg border border-accent/20 bg-accent/5 p-4 text-sm" aria-live="polite">
                            <p class="font-semibold text-neutral-900">Impacto da aceitação</p>
                            <p class="mt-2 text-neutral-600">Já reconhecido nesta categoria: <span x-text="formatHours(category?.recognized ?? 0)"></span> h.</p>
                            <p class="mt-1 font-semibold text-accent">Este documento acrescentará <span x-text="formatHours(additionalHours)"></span> h ao total reconhecido.</p>
                            <p class="mt-2 text-neutral-600" x-show="hours > additionalHours">A carga do certificado será preservada. O excedente de <span x-text="formatHours(Math.max(0, hours - additionalHours))"></span> h não será contabilizado devido ao limite da categoria.</p>
                        </div>

                        <div class="border-t border-neutral-200 pt-5">
                            <div class="flex flex-wrap justify-end gap-3">
                                <x-button type="button" color="danger-outline" @click="$dispatch('modal-open', 'reject-review-{{ $review->id }}')">
                                    <x-heroicon-o-x-circle /> Rejeitar documento
                                </x-button>
                                <x-button type="submit" variant="primary">
                                    <x-heroicon-o-check-circle /> Aceitar documento
                                </x-button>
                            </div>
                        </div>
                    </form>
                    <div x-data x-init="@if ($errors->has('rejection_reason')) $nextTick(() => $dispatch('modal-open', 'reject-review-{{ $review->id }}')) @endif">
                        <x-modal name="reject-review-{{ $review->id }}" title="Rejeitar documento" confirm-variant="warning" hide-footer>
                            <x-slot:content>
                                <p>Explique o que impede a aceitação. O discente poderá enviar outro documento.</p>
                                <form method="POST" action="{{ route('reviews.reject', $review) }}" class="mt-5 space-y-4" x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <x-form-textarea name="rejection_reason" label="Motivo da rejeição" required minlength="5" maxlength="2000" />
                                    <div class="flex justify-end gap-3">
                                        <x-button type="button" color="outline" @click="$dispatch('modal-close', 'reject-review-{{ $review->id }}')">Cancelar</x-button>
                                        <x-button type="submit" color="red">Confirmar rejeição</x-button>
                                    </div>
                                </form>
                            </x-slot:content>
                        </x-modal>
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
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Carga horária do certificado</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->certificate_hours !== null ? number_format((float) $review->certificate_hours, 2, ',', '.').' h' : 'Não informada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Atividade na área</dt>
                            <dd class="mt-1 text-sm text-neutral-900">{{ $review->is_area_related ? 'Sim' : 'Não' }}</dd>
                        </div>
                        @if (data_get($review->rules_snapshot, 'recognized_hours') !== null)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Horas reconhecidas nesta aprovação</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ number_format((float) data_get($review->rules_snapshot, 'recognized_hours'), 2, ',', '.') }} h</dd>
                            </div>
                        @endif
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

                    @can('reopen', $review)
                        <div class="mt-6 border-t border-neutral-200 pt-5">
                            <p class="mb-4 text-sm text-neutral-600">Reabra a análise para corrigir os dados ou alterar a decisão. As horas deixam de ser contabilizadas até uma nova aceitação.</p>
                            <form method="POST" action="{{ route('reviews.reopen', $review) }}">
                                @csrf
                                <x-button type="submit" color="outline"><x-heroicon-o-arrow-path /> Reabrir análise</x-button>
                            </form>
                        </div>
                    @endcan
                @endif
            </x-card>
        @endif
        </div>
        </div>
    </div>
</x-layouts.app>
