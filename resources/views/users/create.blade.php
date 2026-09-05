<x-layouts.app>
    @php
        $defaultType = old('affiliation_type', array_key_first($affiliationTypes));
        $isCoordinator = $activeAffiliation->type === \App\Enums\AffiliationType::Coordinator;
    @endphp

    <x-page-header title="Novo usuário" description="A conta será criada com o primeiro vínculo e receberá um convite para definir a senha.">
        <x-back-button :fallback="route('users.index')" />
    </x-page-header>

    <form method="POST" action="{{ route('users.store') }}" class="mt-6 max-w-4xl space-y-6" x-data="{ type: @js($defaultType) }">
        @csrf

        <x-card>
            <h2 class="text-base font-semibold text-neutral-900">Dados da conta</h2>
            <p class="mt-1 text-sm text-neutral-500">CPF e e-mail de login identificam a conta de acesso.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form-input name="name" label="Nome completo" required autofocus autocomplete="name" />
                </div>
                <x-form-input name="cpf" label="CPF" required inputmode="numeric" maxlength="14" autocomplete="off" />
                <x-form-input name="email" type="email" label="E-mail de login" required autocomplete="email" />
            </div>
        </x-card>

        <x-card>
            <h2 class="text-base font-semibold text-neutral-900">Vínculo inicial</h2>
            <p class="mt-1 text-sm text-neutral-500">Todo usuário precisa de um vínculo institucional para operar no ChronoCert.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    @if (count($affiliationTypes) === 1)
                        <input type="hidden" name="affiliation_type" value="{{ array_key_first($affiliationTypes) }}">
                        <x-form-input label="Tipo de vínculo" :value="reset($affiliationTypes)" readonly />
                    @else
                        <x-form-select name="affiliation_type" label="Tipo de vínculo" x-model="type" @change="if (type === 'administrator') { $refs.course.value = '' }" required>
                            @foreach ($affiliationTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-form-select>
                    @endif
                </div>

                @if ($isCoordinator)
                    <input type="hidden" name="course_id" value="{{ $activeAffiliation->course_id }}">
                    <x-form-input label="Curso" :value="$activeAffiliation->course?->name" readonly />
                @else
                    <div x-show="type !== 'administrator'" x-cloak>
                        <x-form-select name="course_id" label="Curso" x-ref="course">
                            <option value="">Selecione um curso</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </x-form-select>
                    </div>
                @endif

                <div x-show="type === 'student'" x-cloak>
                    <x-form-input name="registration_number" label="Matrícula" :value="old('registration_number')" autocomplete="off" />
                </div>
                <div :class="type === 'student' ? '' : 'sm:col-span-2'">
                    <x-form-input name="operational_email" type="email" label="E-mail operacional do vínculo" required autocomplete="email" help="Usado na atuação acadêmica deste vínculo." />
                </div>
            </div>
        </x-card>

        <x-callout color="accent" title="Convite de acesso">
            O usuário não define senha neste cadastro. O sistema enviará um link de redefinição de senha para o e-mail de login informado.
        </x-callout>

        <div class="flex items-center justify-end gap-3">
            <x-back-button :fallback="route('users.index')" text="Cancelar" />
            <x-button type="submit" color="accent"><x-heroicon-o-user-plus class="size-4" /> Criar usuário e enviar convite</x-button>
        </div>
    </form>
</x-layouts.app>
