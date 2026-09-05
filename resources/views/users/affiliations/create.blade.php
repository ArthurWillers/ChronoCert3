<x-layouts.app>
    @php
        $defaultType = old('affiliation_type', array_key_first($affiliationTypes));
        $isCoordinator = $activeAffiliation->type === \App\Enums\AffiliationType::Coordinator;
    @endphp

    <x-page-header :title="'Novo vínculo para ' . $user->name" description="Crie uma atuação institucional sem alterar a identidade ou o acesso da conta.">
        <x-back-button :fallback="route('users.show', $user)" />
    </x-page-header>

    <form method="POST" action="{{ route('users.affiliations.store', $user) }}" class="mt-6 max-w-3xl space-y-6" x-data="{ type: @js($defaultType) }">
        @csrf

        <x-card>
            <div class="grid gap-5 sm:grid-cols-2">
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
                    <x-form-input name="operational_email" type="email" label="E-mail operacional do vínculo" required autocomplete="email" />
                </div>
            </div>
        </x-card>

        <x-callout color="neutral" title="Transferência de discente">
            Para transferir, a coordenação de destino cria este novo vínculo. A coordenação anterior desativa o vínculo antigo de forma independente.
        </x-callout>

        <div class="flex items-center justify-end gap-3">
            <x-back-button :fallback="route('users.show', $user)" text="Cancelar" />
            <x-button type="submit" color="accent"><x-heroicon-o-plus class="size-4" /> Criar vínculo</x-button>
        </div>
    </form>
</x-layouts.app>
