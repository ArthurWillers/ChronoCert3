<x-layouts.app>
    <x-page-header :title="$user->name" description="Dados da conta e vínculos institucionais deste usuário.">
        <x-back-button :fallback="route('users.index')" />
        @can('addAffiliation', $user)
            <x-button :href="route('users.affiliations.create', $user)" color="accent"><x-heroicon-o-plus class="size-4" /> Adicionar vínculo</x-button>
        @endcan
        @can('delete', $user)
            <x-modal.delete
                :action="route('users.destroy', $user)"
                title="Excluir usuário"
                item-name="este usuário"
                permanent
                button-text="Excluir usuário"
                description="A exclusão é definitiva e só é permitida enquanto a conta não possuir vínculos institucionais."
            />
        @endcan
    </x-page-header>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-6">
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-wider text-neutral-500 uppercase">Conta de acesso</p>
                        <h2 class="mt-2 text-lg font-semibold text-neutral-900">{{ $user->name }}</h2>
                    </div>
                    <x-avatar :model="$user" size="lg" />
                </div>
                <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-neutral-500 uppercase">Nome completo</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $user->name }}</dd>
                    </div>
                    @can('updateIdentity', $user)
                        <div>
                            <dt class="text-xs font-bold tracking-wider text-neutral-500 uppercase">CPF</dt>
                            <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $user->cpf }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold tracking-wider text-neutral-500 uppercase">E-mail de login</dt>
                            <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $user->email }}</dd>
                        </div>
                    @endcan
                </dl>
            </x-card>

            @can('updateIdentity', $user)
                <x-card>
                    <h2 class="text-base font-semibold text-neutral-900">Editar dados do usuário</h2>
                    <p class="mt-1 text-sm text-neutral-500">Nome, CPF e e-mail de login exigem a sua senha atual. Senhas são alteradas somente pelo próprio usuário.</p>
                    <form method="POST" action="{{ route('users.identity.update', $user) }}" class="mt-6 grid gap-5 sm:grid-cols-2">
                        @csrf
                        @method('PATCH')
                        <x-form-input name="name" label="Nome completo" :value="$user->name" required autocomplete="name" />
                        <x-form-input name="cpf" label="CPF" :value="$user->cpf" required inputmode="numeric" maxlength="14" autocomplete="off" />
                        <div class="sm:col-span-2">
                            <x-form-input name="email" type="email" label="E-mail de login" :value="$user->email" required autocomplete="email" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-form-input name="current_password" type="password" label="Sua senha atual" viewable required autocomplete="current-password" />
                        </div>
                        <div class="flex justify-end sm:col-span-2">
                            <x-button type="submit" color="accent">Salvar identificação</x-button>
                        </div>
                    </form>
                </x-card>
            @endcan

            <x-card class="!p-0">
                <div class="flex flex-col justify-between gap-3 border-b border-neutral-100 px-5 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Vínculos</h2>
                        <p class="mt-1 text-sm text-neutral-500">Histórico de atuações acadêmicas e institucionais.</p>
                    </div>
                    @can('sendInvitation', $user)
                        <form method="POST" action="{{ route('users.invitation.send', $user) }}">
                            @csrf
                            <x-button type="submit" color="outline"><x-heroicon-o-paper-airplane class="size-4" /> Reenviar convite</x-button>
                        </form>
                    @endcan
                </div>

                <div class="divide-y divide-neutral-100">
                    @forelse ($affiliations as $affiliation)
                        <div class="p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-neutral-900">{{ $affiliation->type->label() }}</p>
                                        <x-badge :color="$affiliation->isActive() ? 'green' : 'red'" size="sm">
                                            {{ $affiliation->isActive() ? 'Ativo' : 'Desativado' }}
                                        </x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-neutral-600">{{ $affiliation->course?->name ?? 'Atuação institucional global' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @can('update', $affiliation)
                                        <x-button :href="route('users.affiliations.edit', [$user, $affiliation])" color="outline">Editar</x-button>
                                    @endcan
                                    @can('activate', $affiliation)
                                        <form method="POST" action="{{ route('users.affiliations.activate', [$user, $affiliation]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-button type="submit" color="accent">Reativar</x-button>
                                        </form>
                                    @endcan
                                    @can('deactivate', $affiliation)
                                        <x-modal.trigger name="deactivate-affiliation-{{ $affiliation->id }}">
                                            <x-button color="danger-outline">Desativar</x-button>
                                        </x-modal.trigger>
                                    @endcan
                                </div>
                            </div>

                            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                @if ($affiliation->registration_number)
                                    <div>
                                        <dt class="text-xs font-bold tracking-wider text-neutral-500 uppercase">Matrícula</dt>
                                        <dd class="mt-1 font-medium text-neutral-900">{{ $affiliation->registration_number }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs font-bold tracking-wider text-neutral-500 uppercase">E-mail operacional</dt>
                                    <dd class="mt-1 break-all font-medium text-neutral-900">{{ $affiliation->email }}</dd>
                                </div>
                            </dl>
                        </div>

                        @can('deactivate', $affiliation)
                            <x-modal name="deactivate-affiliation-{{ $affiliation->id }}" title="Desativar vínculo" confirm-variant="warning" hide-footer>
                                <x-slot:content>
                                    <p>O vínculo continuará no histórico, mas deixará de ficar disponível para operação.</p>
                                    <form method="POST" action="{{ route('users.affiliations.deactivate', [$user, $affiliation]) }}" class="mt-5 space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <x-form-textarea name="reason" label="Justificativa" help="Opcional. Será registrada na auditoria." />
                                        <div class="flex justify-end gap-3">
                                            <x-button type="button" color="outline" @click="$dispatch('modal-close', 'deactivate-affiliation-{{ $affiliation->id }}')">Cancelar</x-button>
                                            <x-button type="submit" color="red">Desativar vínculo</x-button>
                                        </div>
                                    </form>
                                </x-slot:content>
                            </x-modal>
                        @endcan
                    @empty
                        <x-empty-state title="Nenhum vínculo neste contexto" description="Não há vínculos disponíveis para consulta no contexto de acesso selecionado." icon="heroicon-o-identification" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <x-metadata-card :model="$user" />
    </div>
</x-layouts.app>
