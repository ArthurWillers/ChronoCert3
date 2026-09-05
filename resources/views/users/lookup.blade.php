<x-layouts.app>
    <x-page-header title="Vincular usuário existente" description="Localize a conta pelo CPF exato para criar um vínculo discente no curso selecionado.">
        <x-back-button :fallback="route('users.index')" />
    </x-page-header>

    <div class="mt-6 max-w-2xl space-y-6">
        <x-card>
            <form method="GET" action="{{ route('users.lookup') }}" class="space-y-5">
                <x-form-input name="cpf" label="CPF do usuário" :value="request('cpf')" required inputmode="numeric" maxlength="14" autofocus autocomplete="off" help="A busca não aceita nome, e-mail ou matrícula." />
                <div class="flex justify-end">
                    <x-button type="submit" color="accent"><x-heroicon-o-magnifying-glass class="size-4" /> Localizar usuário</x-button>
                </div>
            </form>
        </x-card>

        @isset ($user)
            <x-card>
                <p class="text-xs font-bold tracking-wider text-neutral-500 uppercase">Usuário localizado</p>
                <h2 class="mt-2 text-lg font-semibold text-neutral-900">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-neutral-500">Confirme para cadastrar um novo vínculo no seu curso ativo.</p>
                <div class="mt-5 flex justify-end">
                    <x-button :href="route('users.affiliations.create', $user)" color="accent"><x-heroicon-o-plus class="size-4" /> Criar vínculo</x-button>
                </div>
            </x-card>
        @endisset
    </div>
</x-layouts.app>
