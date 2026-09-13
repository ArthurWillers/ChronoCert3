<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            title="Comprovantes de ACC"
            :description="$affiliation->type === \App\Enums\AffiliationType::Student
                ? 'Acompanhe os comprovantes enviados no seu vínculo ativo.'
                : 'Consulte e registre comprovantes dos discentes do curso ativo.'"
            :action="$canCreateOwn ? route('submissions.create') : null"
            action-text="Enviar comprovante"
            icon="heroicon-o-arrow-up-tray"
        />

        @if ($canRegisterForStudents)
            <x-card>
                <x-section-header title="Registrar para um discente" icon="heroicon-o-user-plus" />
                <p class="mt-2 text-sm leading-6 text-neutral-600">O comprovante ficará vinculado ao discente selecionado e registrará a coordenação como autora do envio.</p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($students as $student)
                        <a href="{{ route('submissions.students.create', $student) }}" class="rounded-lg border border-neutral-200 p-4 transition hover:border-accent hover:bg-accent/5">
                            <p class="truncate font-semibold text-neutral-900">{{ $student->user->name }}</p>
                            <p class="mt-1 text-sm text-neutral-500">Matrícula {{ $student->registration_number }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-neutral-600">Não há vínculos discentes ativos neste curso.</p>
                    @endforelse
                </div>
            </x-card>
        @endif

        <x-filter-bar :action="route('submissions.index')" :filters="['status']">
            <x-filter-bar.select name="status" aria-label="Situação do comprovante">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\SubmissionStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-filter-bar.select>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[minmax(0,1fr)_160px_140px] sm:grid">
                <x-table.column>COMPROVANTE</x-table.column>
                <x-table.column>ORIGEM</x-table.column>
                <x-table.column align="right">SITUAÇÃO</x-table.column>
            </x-table.header>
            <div class="divide-y divide-neutral-100">
                @forelse ($submissions as $submission)
                    @php($color = match ($submission->status) {
                        \App\Enums\SubmissionStatus::Submitted => 'yellow',
                        \App\Enums\SubmissionStatus::UnderReview => 'blue',
                        \App\Enums\SubmissionStatus::Rejected => 'red',
                        \App\Enums\SubmissionStatus::Approved => 'green',
                    })
                    <x-table.row :href="route('submissions.show', $submission)" class="hidden grid-cols-[minmax(0,1fr)_160px_140px] sm:grid">
                        <x-table.cell>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-neutral-900">{{ $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Comprovante sem arquivo' }}</p>
                                <p class="mt-1 text-sm text-neutral-500">
                                    @if ($affiliation->type === \App\Enums\AffiliationType::Coordinator)
                                        {{ $submission->studentAffiliation->user->name }} ·
                                    @endif
                                    Enviado em {{ $submission->submitted_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ $submission->origin->label() }}</x-table.cell>
                        <x-table.cell align="right"><x-badge :color="$color" size="sm">{{ $submission->status->label() }}</x-badge></x-table.cell>
                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900">{{ $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename') ?? 'Comprovante sem arquivo' }}</p>
                                    <p class="mt-1 text-sm text-neutral-500">{{ $submission->origin->label() }} · {{ $submission->submitted_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <x-badge :color="$color" size="sm">{{ $submission->status->label() }}</x-badge>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state
                        title="Nenhum comprovante encontrado"
                        :description="$canCreateOwn ? 'Envie o primeiro comprovante para iniciar a análise.' : 'Não há comprovantes no curso para os filtros selecionados.'"
                        icon="heroicon-o-document-arrow-up"
                        action-text="Enviar comprovante"
                        :action-route="$canCreateOwn ? route('submissions.create') : null"
                    />
                @endforelse
            </div>
        </x-table>

        {{ $submissions->links() }}
    </div>
</x-layouts.app>
