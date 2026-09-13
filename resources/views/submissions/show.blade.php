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
    </div>
</x-layouts.app>
