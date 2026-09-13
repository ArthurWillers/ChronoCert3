@php
    $acceptedExtensions = \App\Models\AccSubmission::acceptedExtensions();
    $accept = collect($acceptedExtensions)->map(fn (string $extension): string => '.'.$extension)->join(',');
    $action = $isCoordinatorSubmission
        ? route('submissions.students.store', $studentAffiliation)
        : route('submissions.store');
@endphp

<x-layouts.app>
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header
            :title="$isCoordinatorSubmission ? 'Registrar comprovante para discente' : 'Enviar comprovante de ACC'"
            :description="$isCoordinatorSubmission
                ? 'O arquivo será atribuído ao discente e identificado como um envio da coordenação.'
                : 'O envio cria um comprovante em análise. Não há rascunho ou reenvio neste registro.'"
        />

        <x-card>
            <div class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-700">
                <p class="font-semibold text-neutral-900">Beneficiário: {{ $studentAffiliation->user->name }}</p>
                <p class="mt-1">Matrícula {{ $studentAffiliation->registration_number }}</p>
            </div>

            <form id="submission-form" method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                @csrf

                <x-dropzone
                    name="document"
                    :accept="$accept"
                    label="Selecione o comprovante"
                    sublabel="PDF, JPEG, PNG ou WebP, com até 10 MB"
                    required
                />

                <x-callout color="blue">
                    O arquivo ficará em armazenamento privado e só poderá ser visualizado pelo discente beneficiário e pela coordenação do mesmo curso.
                </x-callout>

                <div class="flex justify-end gap-3">
                    <x-form-actions form="submission-form" :fallback="route('submissions.index')" :submit-text="$isCoordinatorSubmission ? 'Registrar comprovante' : 'Enviar comprovante'" />
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
