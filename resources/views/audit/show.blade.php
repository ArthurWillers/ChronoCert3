<x-layouts.app>
    @php
        $actorAffiliation = $auditActivity->reference('actor_affiliation');
    @endphp

    <x-page-header
        :title="$auditActivity->auditEvent()?->label() ?? $auditActivity->description"
        :description="'Evento #' . $auditActivity->getKey() . ' registrado em ' . formatDateTime($auditActivity->created_at)"
    >
        <x-back-button :fallback="route('audit.index')" />
    </x-page-header>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-1">
            <x-card class="!p-0">
                <dl class="divide-y divide-neutral-100">
                    <div class="px-5 py-4">
                        <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Ação</dt>
                        <dd class="mt-2">
                            <x-badge :color="$auditActivity->auditEvent()?->color() ?? 'neutral'" size="sm">
                                {{ $auditActivity->auditEvent()?->label() ?? $auditActivity->description }}
                            </x-badge>
                        </dd>
                    </div>

                    <div class="px-5 py-4">
                        <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Responsável</dt>
                        <dd class="mt-2 flex items-center gap-3">
                            @if ($auditActivity->causer)
                                <x-avatar :model="$auditActivity->causer" size="md" />
                                <span class="text-sm font-medium text-neutral-900">{{ $auditActivity->causer->name }}</span>
                            @else
                                <x-avatar icon="heroicon-o-cpu-chip" size="md" />
                                <span class="text-sm font-medium text-neutral-900">Sistema</span>
                            @endif
                        </dd>
                    </div>

                    @if ($actorAffiliation)
                        <div class="px-5 py-4">
                            <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Vínculo responsável</dt>
                            <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $actorAffiliation['affiliation_type_label'] ?? 'Vínculo' }}</dd>
                            <p class="mt-1 text-xs text-neutral-500">
                                @if (isset($actorAffiliation['id']))
                                    Vínculo técnico #{{ $actorAffiliation['id'] }}
                                @endif
                                @if (filled($actorAffiliation['registration_number'] ?? null))
                                    · Matrícula {{ $actorAffiliation['registration_number'] }}
                                @endif
                                @if (filled($actorAffiliation['course_id'] ?? null))
                                    · {{ data_get($actorAffiliation, 'course.name') ?? 'Curso técnico #' . $actorAffiliation['course_id'] }}
                                @endif
                            </p>
                        </div>
                    @endif

                    <div class="px-5 py-4">
                        <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Origem</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $auditActivity->auditSource()?->label() ?? 'Origem não informada' }}</dd>
                        @if ($auditActivity->getProperty('source.detail'))
                            <p class="mt-1 text-xs text-neutral-500">{{ $auditActivity->getProperty('source.detail') }}</p>
                        @endif
                    </div>

                    <div class="px-5 py-4">
                        <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Data e hora</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ formatDateTime($auditActivity->created_at) }}</dd>
                    </div>

                    <div class="px-5 py-4">
                        <dt class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Registro afetado</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $auditActivity->subjectLabel() }}</dd>
                    </div>
                </dl>
            </x-card>

            @if ($auditActivity->getProperty('reason'))
                <x-card>
                    <p class="text-xxs font-bold tracking-wider text-neutral-500 uppercase">Justificativa registrada</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-neutral-700">{{ $auditActivity->getProperty('reason') }}</p>
                </x-card>
            @endif
        </div>

        <div class="space-y-6 xl:col-span-2">
            <x-card>
                <h2 class="text-lg font-semibold text-neutral-900">Referências do evento</h2>
                <p class="mt-1 text-sm text-neutral-500">Snapshots preservados para entender a ação mesmo se o registro original deixar de existir.</p>

                @php
                    $references = $auditActivity->references();
                    $subjectReference = $references['subject'] ?? [];
                @endphp
                <dl class="mt-5 divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                    @foreach ($references as $name => $reference)
                        @php
                            $sameAsSubject = ($reference['id'] ?? null) !== null
                                && ($reference['id'] ?? null) === ($subjectReference['id'] ?? null)
                                && ($reference['type'] ?? null) === ($subjectReference['type'] ?? null);
                            $sameAsSubjectPerson = ($subjectReference['type'] ?? null) === 'affiliation'
                                && ($reference['type'] ?? null) === 'user'
                                && ($reference['id'] ?? null) === data_get($subjectReference, 'user.id');
                            $isRedundantReference = $name === 'causer' || $sameAsSubject || $sameAsSubjectPerson;
                        @endphp
                        @if ($name !== 'subject' && $isRedundantReference)
                            @continue
                        @endif
                        @php
                            $label = match ($name) {
                                'subject' => 'Registro afetado',
                                'user' => 'Usuário',
                                'causer' => 'Responsável',
                                'course' => 'Curso',
                                'category' => 'Categoria',
                                'submission' => 'Comprovante',
                                'author_affiliation' => 'Vínculo autor',
                                'reviewer_affiliation' => 'Vínculo avaliador',
                                'student_affiliation' => 'Vínculo discente',
                                'target_affiliation' => 'Vínculo alvo',
                                'selected_affiliation' => 'Vínculo selecionado',
                                'actor_affiliation' => 'Vínculo responsável',
                                default => 'Referência',
                            };
                            $value = $name === 'actor_affiliation'
                                ? ($reference['affiliation_type_label'] ?? 'Vínculo')
                                : ($reference['title'] ?? $reference['name'] ?? data_get($reference, 'user.name') ?? $reference['label'] ?? 'Registro técnico #' . ($reference['id'] ?? '—'));
                            $details = array_filter([
                                isset($reference['id']) ? 'ID técnico: #' . $reference['id'] : null,
                                $name !== 'actor_affiliation' ? ($reference['affiliation_type_label'] ?? null) : null,
                                isset($reference['registration_number']) ? 'Matrícula: ' . $reference['registration_number'] : null,
                                isset($reference['course_id']) ? 'Curso: ' . (data_get($reference, 'course.name') ?? '#' . $reference['course_id']) : null,
                            ]);
                        @endphp
                        <div class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <dt class="text-sm font-medium text-neutral-600">{{ $label }}</dt>
                            <dd class="text-sm font-semibold text-neutral-900 sm:text-right">
                                {{ $value }}
                                @if ($details !== [])
                                    <span class="ml-1 font-normal text-neutral-500">({{ implode(' · ', $details) }})</span>
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>

            @if ($auditActivity->changes() !== [])
                <x-card>
                    <h2 class="text-lg font-semibold text-neutral-900">Dados e alterações</h2>
                    <p class="mt-1 text-sm text-neutral-500">Valores protegidos permanecem ocultos, mas a alteração é preservada.</p>

                    <div class="mt-5 overflow-hidden rounded-xl border border-neutral-200">
                        @foreach ($auditActivity->changes() as $attribute => $change)
                            <div class="grid grid-cols-1 gap-2 border-b border-neutral-100 px-4 py-3 last:border-0 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)] sm:items-center sm:gap-4">
                                @php
                                    $attributeLabel = match ($attribute) {
                                        'name' => 'Nome',
                                        'type' => 'Tipo de vínculo',
                                        'deactivated_at' => 'Desativação',
                                        'last_used_at' => 'Último uso',
                                        'registration_number' => 'Matrícula',
                                        'course', 'course_id' => 'Curso',
                                        'category_id' => 'Categoria',
                                        'title' => 'Título',
                                        'status' => 'Situação',
                                        'hours', 'approved_hours' => 'Horas',
                                        'required_acc_hours' => 'Carga horária total exigida',
                                        'minimum_area_percentage' => 'Percentual mínimo na área',
                                        'cpf' => 'CPF',
                                        'email' => 'E-mail',
                                        'operational_email' => 'E-mail operacional',
                                        'password' => 'Senha',
                                        default => 'Dado atualizado',
                                    };
                                @endphp
                                <p class="text-sm font-medium text-neutral-800">{{ $attributeLabel }}</p>
                                @if (is_array($change) && ($change['changed'] ?? false) === true)
                                    <p class="text-sm text-neutral-500 sm:col-span-2">Campo protegido alterado.</p>
                                @else
                                    <p class="text-sm text-red-700">
                                        <span class="mr-1 text-xs font-medium text-neutral-400 sm:hidden">Anterior:</span>
                                        {{ is_array($change) ? ($change['old'] ?? '—') : '—' }}
                                    </p>
                                    <p class="text-sm text-green-700">
                                        <span class="mr-1 text-xs font-medium text-neutral-400 sm:hidden">Atual:</span>
                                        {{ is_array($change) ? ($change['new'] ?? '—') : $change }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
