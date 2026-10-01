<x-layouts.app>
    <div class="mx-auto max-w-xl space-y-6">
        <x-page-header
            title="Selecionar vínculo"
            description="Escolha o perfil e o curso para acessar o painel."
        />

        @if ($affiliations->isEmpty())
            <x-card>
                <x-empty-state
                    title="Nenhum vínculo disponível"
                    description="Sua conta ainda não possui um vínculo institucional ativo."
                    icon="heroicon-o-identification"
                />
            </x-card>
        @else
            <form method="POST" action="{{ route('affiliations.select.store') }}">
                @csrf

                <x-card class="space-y-6">
                    <x-form-select name="affiliation_id" label="Vínculo" required autofocus>
                        <option value="">Selecione um vínculo</option>
                        @foreach ($affiliations as $affiliation)
                            <option value="{{ $affiliation->getKey() }}" @selected((string) old('affiliation_id', session('active_affiliation_id')) === (string) $affiliation->getKey())>
                                {{ $affiliation->type->label() }} — {{ $affiliation->course?->name ?? 'Atuação institucional' }}
                            </option>
                        @endforeach
                    </x-form-select>

                    <div class="flex justify-end">
                        <x-button type="submit" color="accent">Usar vínculo</x-button>
                    </div>
                </x-card>
            </form>
        @endif
    </div>
</x-layouts.app>
