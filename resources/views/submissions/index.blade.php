@php
    $indexRoute = $isCoordinator
        ? route('submissions.students.index', $studentAffiliation)
        : route('submissions.index');
    $downloadRoute = $isCoordinator
        ? route('submissions.students.download-all', [$studentAffiliation, ...request()->only(['status', 'acc_category_id'])])
        : route('submissions.download-all', request()->only(['status', 'acc_category_id']));
    $createRoute = $isCoordinator
        ? route('submissions.students.create', $studentAffiliation)
        : route('submissions.create');
@endphp

<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            :title="$isCoordinator ? 'Documentos de '.$studentAffiliation->user->name : 'Meus documentos'"
            :description="$isCoordinator
                ? 'Matrícula '.$studentAffiliation->registration_number.' · '.$studentAffiliation->course->name
                : 'Acompanhe os comprovantes do seu vínculo ativo.'"
            :action="($canCreateOwn || $canCreateFor) ? $createRoute : null"
            :action-text="$isCoordinator ? 'Registrar documento' : 'Enviar documento'"
            icon="heroicon-o-document-text"
        >
            @if ($isCoordinator)
                <x-back-button :fallback="route('users.show', $studentAffiliation->user)" />
            @endif
        </x-page-header>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-neutral-600">O download inclui apenas os arquivos deste discente que correspondem aos filtros selecionados.</p>
            @if ($archiveHasFiles)
                <x-button :href="$downloadRoute" color="outline" class="shrink-0">
                    <x-heroicon-o-arrow-down-tray /> Baixar arquivos filtrados (ZIP)
                </x-button>
            @else
                <span class="inline-flex min-h-10 shrink-0 items-center rounded-lg border border-neutral-200 bg-neutral-50 px-3 text-sm font-semibold text-neutral-400" aria-disabled="true">Nenhum arquivo para baixar</span>
            @endif
        </div>

        <x-filter-bar :action="$indexRoute" :filters="['status', 'acc_category_id']" :show-search="false">
            <x-filter-bar.select name="acc_category_id" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->getKey() }}" @selected((string) request('acc_category_id') === (string) $category->getKey())>{{ $category->name }}</option>
                @endforeach
            </x-filter-bar.select>
            <x-filter-bar.select name="status" aria-label="Situação do documento">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\SubmissionStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-filter-bar.select>
        </x-filter-bar>

        <x-table>
            <x-table.header class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_130px] sm:grid">
                <x-table.column>DOCUMENTO</x-table.column>
                <x-table.column>CATEGORIA</x-table.column>
                <x-table.column align="right">SITUAÇÃO</x-table.column>
            </x-table.header>
            <div class="divide-y divide-neutral-100">
                @forelse ($submissions as $submission)
                    @php
                        $color = match ($submission->status) {
                            \App\Enums\SubmissionStatus::Submitted => 'yellow',
                            \App\Enums\SubmissionStatus::UnderReview => 'blue',
                            \App\Enums\SubmissionStatus::Rejected => 'red',
                            \App\Enums\SubmissionStatus::Approved => 'green',
                        };
                        $documentName = $submission->review?->normalized_title
                            ?? $submission->getFirstMedia(\App\Models\AccSubmission::EvidenceCollection)?->getCustomProperty('original_filename')
                            ?? 'Documento sem arquivo';
                        $categoryName = data_get($submission->review?->category_snapshot, 'name', $submission->review?->category?->name ?? 'Sem categoria');
                    @endphp
                    <x-table.row :href="route('submissions.show', $submission)" class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)_130px] sm:grid">
                        <x-table.cell>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-neutral-900">{{ $documentName }}</p>
                                <p class="mt-1 text-sm text-neutral-500">{{ $submission->origin->label() }} · {{ $submission->submitted_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="truncate text-sm text-neutral-600">{{ $categoryName }}</x-table.cell>
                        <x-table.cell align="right"><x-badge :color="$color" size="sm">{{ $submission->status->label() }}</x-badge></x-table.cell>
                        <x-slot:mobile>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-neutral-900">{{ $documentName }}</p>
                                    <p class="mt-1 text-sm text-neutral-500">{{ $categoryName }} · {{ $submission->submitted_at->format('d/m/Y') }}</p>
                                </div>
                                <x-badge :color="$color" size="sm">{{ $submission->status->label() }}</x-badge>
                            </div>
                        </x-slot:mobile>
                    </x-table.row>
                @empty
                    <x-empty-state
                        title="Nenhum documento encontrado"
                        description="Não há comprovantes para os filtros selecionados."
                        icon="heroicon-o-document-text"
                        action-text="Enviar documento"
                        :action-route="($canCreateOwn || $canCreateFor) ? $createRoute : null"
                    />
                @endforelse
            </div>
        </x-table>

        {{ $submissions->links() }}
    </div>
</x-layouts.app>
