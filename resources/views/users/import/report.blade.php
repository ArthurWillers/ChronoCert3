<x-layouts.app>
    @php
        $statusLabels = [
            'new_user' => ['Conta e vínculo criados', 'green'],
            'new_affiliation' => ['Vínculo criado', 'blue'],
            'reactivation' => ['Vínculo reativado', 'yellow'],
            'ignored' => ['Ignorado', 'neutral'],
            'invalid' => ['Inválido na prévia', 'red'],
            'failed' => ['Não processado', 'red'],
        ];
    @endphp

    <x-page-header title="Resultado da importação" :description="$activeAffiliation->course->name">
        <x-button :href="route('users.import.create')" color="outline"><x-heroicon-o-arrow-up-tray class="size-4" /> Nova importação</x-button>
        <x-back-button :fallback="route('users.index')" text="Usuários" />
    </x-page-header>

    <div class="mt-6 space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <x-card><p class="text-sm text-neutral-500">Contas criadas</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['users_created'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Vínculos criados</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['affiliations_created'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Reativações</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['reactivated'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Ignorados</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['ignored'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Falhas</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['failed'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Inválidos</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['invalid'] }}</p></x-card>
        </div>

        @if ($summary['users_created'] > 0)
            <x-callout color="accent" title="Convites de acesso agendados">
                As contas novas receberão um convite para definir a senha no e-mail informado no arquivo.
            </x-callout>
        @endif

        <div class="overflow-x-auto">
        <x-table>
            <x-table.header class="hidden min-w-[900px] grid-cols-[60px_minmax(180px,1fr)_140px_160px_minmax(230px,1.2fr)] sm:grid">
                <x-table.column>Linha</x-table.column>
                <x-table.column>Nome informado</x-table.column>
                <x-table.column>CPF</x-table.column>
                <x-table.column>Matrícula</x-table.column>
                <x-table.column>Resultado</x-table.column>
            </x-table.header>

            <div class="divide-y divide-neutral-100">
                @foreach ($rows as $row)
                    @php
                        [$statusLabel, $statusColor] = $statusLabels[$row['status']];
                        $maskedCpf = strlen($row['cpf']) === 11
                            ? substr($row['cpf'], 0, 3) . '.***.**' . substr($row['cpf'], -2)
                            : ($row['cpf'] !== '' ? $row['cpf'] : '—');
                    @endphp
                    <x-table.row class="min-w-[900px] grid-cols-[60px_minmax(180px,1fr)_140px_160px_minmax(230px,1.2fr)]">
                        <x-table.cell class="text-sm text-neutral-500">{{ $row['number'] }}</x-table.cell>
                        <x-table.cell><span class="truncate text-sm font-medium text-neutral-900">{{ $row['name'] !== '' ? $row['name'] : '—' }}</span></x-table.cell>
                        <x-table.cell class="text-sm text-neutral-600">{{ $maskedCpf }}</x-table.cell>
                        <x-table.cell class="text-sm text-neutral-600">{{ $row['registration_number'] !== '' ? $row['registration_number'] : '—' }}</x-table.cell>
                        <x-table.cell>
                            <x-badge :color="$statusColor" size="sm">{{ $statusLabel }}</x-badge>
                            @if ($row['errors'] !== [])
                                <ul class="mt-1 space-y-0.5 text-xs text-red-700">
                                    @foreach ($row['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </x-table.cell>
                    </x-table.row>
                @endforeach
            </div>
        </x-table>
        </div>
    </div>
</x-layouts.app>
