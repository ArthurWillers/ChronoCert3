<x-layouts.app>
    <x-page-header title="Novo curso" description="Cadastre um curso que poderá receber vínculos institucionais.">
        <x-back-button :fallback="route('courses.index')" />
    </x-page-header>

    <x-card class="mt-6 max-w-2xl">
        <form
            method="POST"
            action="{{ route('courses.store') }}"
            class="space-y-6"
            x-data="{ hasAreaRequirement: @js((bool) old('has_area_requirement')) }"
        >
            @csrf

            <x-form-input name="name" label="Nome do curso" required autofocus autocomplete="off" />

            <div class="space-y-4 border-t border-neutral-100 pt-6">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Regras de ACC</h2>
                    <p class="mt-1 text-sm leading-6 text-neutral-500">São opcionais e poderão ser personalizados para cada curso.</p>
                </div>

                <x-form-input
                    name="required_acc_hours"
                    label="Carga horária total exigida"
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
                    :checked="old('has_area_requirement')"
                >
                    Exigir um mínimo de atividades na área de formação
                </x-form-checkbox>

                <div x-show="hasAreaRequirement" x-cloak>
                    <x-form-input
                        name="minimum_area_percentage"
                        label="Percentual mínimo na área"
                        placeholder="Ex.: 50"
                        numeric
                        inputmode="decimal"
                        autocomplete="off"
                        x-bind:disabled="!hasAreaRequirement"
                        help="O sistema aplicará este percentual sobre a carga horária total exigida."
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-neutral-100 pt-5">
                <x-back-button :fallback="route('courses.index')" text="Cancelar" />
                <x-button type="submit" color="accent">
                    <x-heroicon-o-check class="size-4" /> Salvar curso
                </x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
