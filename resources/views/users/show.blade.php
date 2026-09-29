<x-layouts.app>
    <x-page-header
        :title="$user->name"
        :description="$activeAffiliation->type === \App\Enums\AffiliationType::Coordinator
            ? 'Consulte o vínculo, os documentos e o resumo acadêmico deste discente.'
            : 'Dados da conta e vínculos institucionais deste usuário.'"
    >
        <x-back-button :fallback="route('users.index')" />
        @if ($activeAffiliation->type === \App\Enums\AffiliationType::Administrator)
            <x-button :href="route('users.affiliations.create', $user)" color="accent"><x-heroicon-o-plus class="size-4" /> Adicionar vínculo</x-button>
        @endif
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

    <div class="mt-5 max-w-4xl">
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
                <div class="flex flex-col justify-between gap-3 border-b border-neutral-100 px-4 py-4 sm:flex-row sm:items-center sm:px-5">
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
                        <div class="p-4 sm:p-5">
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
                                <div class="flex flex-wrap gap-1.5">
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

                            @if ($activeAffiliation->type === \App\Enums\AffiliationType::Coordinator && $affiliation->type === \App\Enums\AffiliationType::Student)
                                @php($summary = $studentSummaries->get($affiliation->getKey()))

                                <section class="mt-5 border-t border-neutral-100 pt-4" aria-labelledby="documents-{{ $affiliation->id }}">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 id="documents-{{ $affiliation->id }}" class="text-sm font-semibold text-neutral-900">Resumo de documentos</h3>
                                            <p class="mt-1 text-sm text-neutral-500">A carga do certificado é preservada no documento. O total é limitado apenas no cálculo de cada categoria.</p>
                                        </div>
                                        @can('createFor', [\App\Models\AccSubmission::class, $affiliation])
                                            <x-button :href="route('submissions.students.create', $affiliation)" color="accent">
                                                <x-heroicon-o-arrow-up-tray /> Registrar documento
                                            </x-button>
                                        @endcan
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        <div class="rounded-lg bg-neutral-50 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Documentos aceitos</p>
                                            <p class="mt-1 text-lg font-semibold text-neutral-900">{{ $summary['acceptedDocumentsCount'] }}</p>
                                        </div>
                                        <div class="rounded-lg bg-neutral-50 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Horas nas categorias</p>
                                            <p class="mt-1 text-lg font-semibold text-neutral-900">{{ number_format($summary['recognizedHours'], 2, ',', '.') }} h</p>
                                        </div>
                                        @if ($summary['minimumAreaHours'] !== null)
                                            <div class="rounded-lg bg-neutral-50 p-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Atividades na área</p>
                                                <p class="mt-1 text-lg font-semibold text-neutral-900">{{ number_format($summary['recognizedAreaHours'], 2, ',', '.') }} h <span class="text-sm font-medium text-neutral-500">de {{ number_format($summary['minimumAreaHours'], 2, ',', '.') }} h</span></p>
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route('submissions.students.index', $affiliation) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent hover:underline">
                                        Ver todos os documentos
                                        <x-heroicon-o-arrow-right class="size-4" />
                                    </a>

                                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                        @forelse ($summary['categorySummaries'] as $categorySummary)
                                            <a
                                                href="{{ route('submissions.students.index', [$affiliation, 'acc_category_id' => $categorySummary['category']->getKey()]) }}"
                                                class="group rounded-lg border border-neutral-200 p-3 transition-colors hover:border-accent hover:bg-accent/5"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-neutral-900 group-hover:text-accent">{{ $categorySummary['category']->name }}</p>
                                                        <p class="mt-1 text-xs text-neutral-500">{{ $categorySummary['accepted_documents_count'] }} {{ $categorySummary['accepted_documents_count'] === 1 ? 'documento aceito' : 'documentos aceitos' }}</p>
                                                    </div>
                                                    <x-heroicon-o-chevron-right class="size-4 shrink-0 text-neutral-400 group-hover:text-accent" />
                                                </div>
                                                <p class="mt-3 text-sm font-medium text-neutral-800">{{ number_format($categorySummary['recognized_hours'], 2, ',', '.') }} h <span class="font-normal text-neutral-500">de {{ number_format((float) $categorySummary['category']->max_hours, 2, ',', '.') }} h na categoria</span></p>
                                                @if ($categorySummary['recognized_area_hours'] > 0)
                                                    <p class="mt-1 text-xs text-neutral-500">{{ number_format($categorySummary['recognized_area_hours'], 2, ',', '.') }} h na área</p>
                                                @endif
                                            </a>
                                        @empty
                                            <p class="text-sm text-neutral-500">Nenhuma categoria cadastrada para este curso.</p>
                                        @endforelse
                                    </div>
                                </section>
                            @endif
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

    </div>
</x-layouts.app>
