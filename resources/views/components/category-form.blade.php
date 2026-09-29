@props(['category' => null])

<div class="space-y-6">
    <x-form-input name="name" label="Nome da categoria" :value="$category?->name" required maxlength="255" autofocus autocomplete="off" />
    <x-form-textarea name="description" label="Descrição" :value="$category?->description" help="Descreva as atividades abrangidas por esta categoria." />

    <div class="space-y-4 border-t border-neutral-100 pt-6">
        <div>
            <h2 class="text-base font-semibold text-neutral-900">Regras acadêmicas</h2>
            <p class="mt-1 text-sm leading-6 text-neutral-500">Defina os parâmetros que serão usados no cálculo da categoria.</p>
        </div>
        <x-form-input name="max_hours" label="Limite de horas da categoria" :value="$category?->max_hours" required inputmode="decimal" placeholder="Ex.: 40" help="Informe um valor positivo, com até duas casas decimais." />
    </div>
</div>
