<x-layouts.app>
    @php
        $actionableCount = $summary['new_users'] + $summary['new_affiliations'] + $summary['reactivations'];
        $statusLabels = [
            'new_user' => ['Nova conta e vínculo', 'green'],
            'new_affiliation' => ['Novo vínculo', 'blue'],
            'reactivation' => ['Reativação', 'yellow'],
            'ignored' => ['Ignorado', 'neutral'],
            'invalid' => ['Inválido', 'red'],
        ];
    @endphp

    <x-page-header title="Conferir importação" :description="$activeAffiliation->course->name">
        <x-back-button :fallback="route('users.import.create')" text="Trocar arquivo" />
    </x-page-header>

    <div class="mt-6 space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <x-card><p class="text-sm text-neutral-500">Novas contas</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['new_users'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Novos vínculos</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['new_affiliations'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Reativações</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['reactivations'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Ignorados</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['ignored'] }}</p></x-card>
            <x-card><p class="text-sm text-neutral-500">Inválidos</p><p class="mt-1 text-2xl font-semibold text-neutral-900">{{ $summary['invalid'] }}</p></x-card>
        </div>

        @if ($summary['invalid'] > 0)
            <x-callout color="yellow" title="Algumas linhas precisam de correção">
                As linhas válidas podem ser confirmadas agora. Corrija os erros indicados e envie outro arquivo para incluir as linhas inválidas.
            </x-callout>
        @endif

        @if ($actionableCount === 0)
            <x-callout color="neutral" title="Nenhuma alteração disponível">
                O arquivo não contém linhas que criem ou reativem vínculos. Confira os dados e envie uma versão atualizada.
            </x-callout>
        @endif

        <div class="overflow-x-auto">
        <x-table>
            <x-table.header class="hidden min-w-[900px] grid-cols-[60px_minmax(180px,1fr)_140px_160px_minmax(230px,1.2fr)] sm:grid">
                <x-table.column>Linha</x-table.column>
                <x-table.column>Nome informado</x-table.column>
                <x-table.column>CPF</x-table.column>
                <x-table.column>Matrícula</x-table.column>
                <x-table.column>Situação e detalhes</x-table.column>
            </x-table.header>

            <div class="divide-y divide-neutral-100">
                @foreach ($rows as $row)
                    @php
                        [$statusLabel, $statusColor] = $statusLabels[$row['status']];
                        $maskedCpf = strlen($row['cpf']) === 11
                            ? substr($row['cpf'], 0, 3) . '.***.**' . substr($row['cpf'], -2)
                            : '—';
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
                            @elseif ($row['status'] === 'ignored')
                                <p class="mt-1 text-xs text-neutral-500">Já existe um vínculo discente ativo neste curso.</p>
                            @endif
                        </x-table.cell>
                    </x-table.row>
                @endforeach
            </div>
        </x-table>
        </div>

        <form method="POST" action="{{ route('users.import.confirm') }}" class="flex flex-wrap items-center justify-between gap-3">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <p class="max-w-2xl text-sm text-neutral-500">A prévia expira em 30 minutos. As linhas aceitas serão revalidadas antes de criar ou reativar vínculos.</p>
            <div class="flex items-center gap-3">
                <x-back-button :fallback="route('users.import.create')" text="Cancelar" />
                <x-button type="submit" color="accent" :disabled="$actionableCount === 0">
                    <x-heroicon-o-check class="size-4" /> Confirmar {{ $actionableCount }} alterações
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
