<x-layouts.app>
    <x-page-header :title="$course->name" description="Atualize os dados institucionais e a situação do curso.">
        <x-back-button :fallback="route('courses.index')" />
        @if ($course->deactivated_at === null)
            @can('deactivate', $course)
                <x-modal.trigger name="deactivate-course-{{ $course->id }}">
                    <x-button color="danger-outline">
                        <x-heroicon-o-no-symbol class="size-4" /> Inativar
                    </x-button>
                </x-modal.trigger>
            @endcan
        @else
            @can('reactivate', $course)
                <form method="POST" action="{{ route('courses.reactivate', $course) }}">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" color="accent">
                        <x-heroicon-o-arrow-path class="size-4" /> Reativar
                    </x-button>
                </form>
            @endcan
        @endif
    </x-page-header>

    <div class="mt-6 grid max-w-5xl gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <x-card>
            <form
                method="POST"
                action="{{ route('courses.update', $course) }}"
                class="space-y-6"
                x-data="{ hasAreaRequirement: @js((bool) old('has_area_requirement', $course->hasAreaRequirement())) }"
            >
                @csrf
                @method('PUT')

                <x-form-input name="name" label="Nome do curso" :value="$course->name" required autocomplete="off" />

                <div class="space-y-4 border-t border-neutral-100 pt-6">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Regras de ACC</h2>
                        <p class="mt-1 text-sm leading-6 text-neutral-500">Defina somente as exigências acadêmicas que se aplicam a este curso.</p>
                    </div>

                    <x-form-input
                        name="required_acc_hours"
                        label="Carga horária total exigida"
                        :value="$course->required_acc_hours"
                        placeholder="Ex.: 80"
                        numeric
                        inputmode="decimal"
                        autocomplete="off"
                        help="Deixe em branco se o curso não possuir uma carga total de ACC definida."
                    />

                    <x-form-checkbox
                        name="has_area_requirement"
                        value="1"
                        x-model="hasAreaRequirement"
                        :checked="old('has_area_requirement', $course->hasAreaRequirement())"
                    >
                        Exigir um mínimo de atividades na área de formação
                    </x-form-checkbox>

                    <div x-show="hasAreaRequirement" x-cloak>
                        <x-form-input
                            name="minimum_area_percentage"
                            label="Percentual mínimo na área"
                            :value="$course->minimum_area_percentage"
                            placeholder="Ex.: 50"
                            numeric
                            inputmode="decimal"
                            autocomplete="off"
                            x-bind:disabled="!hasAreaRequirement"
                            help="O sistema aplicará este percentual sobre a carga horária total exigida."
                        />
                    </div>
                </div>

                <div class="flex justify-end border-t border-neutral-100 pt-5">
                    <x-button type="submit" color="accent">
                        <x-heroicon-o-check class="size-4" /> Salvar alterações
                    </x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-6">
            <x-metadata-card :model="$course" />

            <x-card>
                <h2 class="text-sm font-semibold text-neutral-900">Situação</h2>
                <div class="mt-3">
                    <x-badge :color="$course->deactivated_at === null ? 'green' : 'red'">
                        {{ $course->deactivated_at === null ? 'Ativo para novos vínculos' : 'Inativo para novos vínculos' }}
                    </x-badge>
                </div>
                @if ($course->deactivated_at !== null)
                    <p class="mt-3 text-sm text-neutral-500">Inativado em {{ formatDateTime($course->deactivated_at) }}.</p>
                @endif
            </x-card>

            @if ($course->hasAccRequirement())
                <x-card>
                    <h2 class="text-sm font-semibold text-neutral-900">Exigências de ACC</h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-neutral-500">Carga total</dt>
                            <dd class="font-semibold text-neutral-900">{{ (float) $course->required_acc_hours }} horas</dd>
                        </div>
                        @if ($course->hasAreaRequirement())
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-neutral-500">Mínimo na área</dt>
                                <dd class="text-right font-semibold text-neutral-900">
                                    {{ (float) $course->minimum_area_percentage }}% <span class="font-normal text-neutral-500">({{ $course->minimumAreaHours() }} horas)</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-card>
            @endif

            @can('delete', $course)
                <x-card class="border-red-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Excluir curso</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-500">A exclusão é permitida porque este curso ainda não possui vínculos de domínio.</p>
                    <x-modal.delete
                        :action="route('courses.destroy', $course)"
                        title="Excluir curso"
                        item-name="este curso"
                        permanent
                        button-text="Excluir curso"
                        button-class="mt-4 w-full"
                        description="Como não há dependências de domínio, o curso poderá ser removido agora."
                    />
                </x-card>
            @endcan
        </div>
    </div>

    @if ($course->deactivated_at === null)
        <x-modal name="deactivate-course-{{ $course->id }}" title="Inativar curso" confirm-variant="warning" hide-footer>
            <x-slot:content>
                <p>Novos vínculos não poderão ser criados neste curso enquanto ele estiver inativo.</p>
                <form method="POST" action="{{ route('courses.deactivate', $course) }}" class="mt-5 flex justify-end gap-3">
                    @csrf
                    @method('PATCH')
                    <x-button type="button" color="outline" @click="$dispatch('modal-close', 'deactivate-course-{{ $course->id }}')">Cancelar</x-button>
                    <x-button type="submit" color="red">Inativar curso</x-button>
                </form>
            </x-slot:content>
        </x-modal>
    @endif

</x-layouts.app>
