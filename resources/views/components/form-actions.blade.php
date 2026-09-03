@props([
    'fallback', 
    'form', 
    'submitText' => 'Salvar', 
    'mobile' => false
])

@if ($mobile)
    {{-- Botões mobile (final da página) --}}
    <div class="flex md:hidden items-center justify-between mt-6">
        <x-back-button :fallback="$fallback" text="Cancelar" />

        <x-button type="submit" :form="$form">
            <x-heroicon-o-check class="size-4" />
            {{ $submitText }}
        </x-button>
    </div>
@else
    {{-- Botões desktop (dentro do header) --}}
    <x-back-button :fallback="$fallback" text="Cancelar" />

    <x-button type="submit" :form="$form">
        <x-heroicon-o-check class="size-4" />
        {{ $submitText }}
    </x-button>
@endif

