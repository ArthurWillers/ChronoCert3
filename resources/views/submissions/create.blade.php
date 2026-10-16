@php
    $acceptedExtensions = \App\Models\AccSubmission::acceptedExtensions();
    $accept = collect($acceptedExtensions)->map(fn (string $extension): string => '.'.$extension)->join(',');
    $action = $isCoordinatorSubmission
        ? route('submissions.students.store', $studentAffiliation)
        : route('submissions.store');
@endphp

<x-layouts.app>
    <div class="mx-auto w-full max-w-4xl space-y-6">
        <x-page-header
            :title="$isCoordinatorSubmission ? 'Registrar documento para discente' : 'Enviar documento de ACC'"
            :description="$isCoordinatorSubmission
                ? 'Ao registrar, o documento abrirá diretamente na etapa de revisão acadêmica.'
                : 'Envie um arquivo por documento. Comprovantes já registrados abrirão o envio existente.'"
        />

        <x-card>
            <div class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-700">
                <p class="font-semibold text-neutral-900">Beneficiário: {{ $studentAffiliation->user->name }}</p>
                <p class="mt-1">Matrícula {{ $studentAffiliation->registration_number }}</p>
            </div>

            <form id="submission-form" method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ loading: false }" @submit="if (loading) { $event.preventDefault(); return; } loading = true">
                @csrf

                <x-dropzone
                    name="document"
                    :accept="$accept"
                    label="Selecione o documento"
                    sublabel="PDF, JPEG, PNG ou WebP, com até 10 MB"
                    required
                />

                <x-callout color="blue">
                    O arquivo ficará em armazenamento privado e só poderá ser visualizado pelo discente beneficiário e pela coordenação do mesmo curso.
                </x-callout>
                <p x-show="loading" x-cloak role="status" class="flex items-center gap-2 text-sm font-medium text-accent"><x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" /> Enviando documento. Aguarde a confirmação.</p>

                <div class="flex justify-end gap-3">
                    <x-form-actions
                        form="submission-form"
                        :fallback="$isCoordinatorSubmission ? route('users.show', $studentAffiliation->user) : route('submissions.index')"
                        :submit-text="$isCoordinatorSubmission ? 'Registrar documento' : 'Enviar documento'"
                    />
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
