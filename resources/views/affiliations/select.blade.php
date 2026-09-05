<x-layouts.app>
    <div class="mx-auto max-w-4xl">
        <x-page-header
            title="Escolha o vínculo de operação"
            description="Selecione como você deseja trabalhar no ChronoCert nesta sessão."
        />

        @if ($affiliations->isEmpty())
            <x-card>
                <x-empty-state
                    title="Nenhum vínculo disponível"
                    description="Sua conta ainda não possui um vínculo institucional ativo."
                    icon="heroicon-o-identification"
                />
            </x-card>
        @else
            <form method="POST" action="{{ route('affiliations.select.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($affiliations as $affiliation)
                        <label class="group relative block h-full cursor-pointer">
                            <input
                                type="radio"
                                name="affiliation_id"
                                value="{{ $affiliation->getKey() }}"
                                class="peer sr-only"
                                @checked((string) old('affiliation_id') === (string) $affiliation->getKey())
                                required
                            />
                            <x-card class="h-full border-2 border-neutral-200 transition-colors peer-checked:border-accent peer-checked:bg-accent/5">
                                <div class="flex items-start gap-4">
                                    <x-avatar icon="heroicon-o-identification" variant="accent" size="lg" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-accent">Vínculo institucional</p>
                                                <h2 class="mt-1 text-lg font-semibold text-neutral-900">{{ $affiliation->type->label() }}</h2>
                                            </div>
                                            <x-heroicon-o-check-circle class="size-6 text-accent opacity-0 transition-opacity peer-checked:opacity-100" />
                                        </div>
                                        <dl class="mt-5 space-y-2 text-sm text-neutral-600">
                                            @if ($affiliation->course)
                                                <div class="flex items-center gap-2">
                                                    <dt class="font-medium text-neutral-800">Curso:</dt>
                                                    <dd class="truncate">{{ $affiliation->course->name }}</dd>
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-2">
                                                <dt class="font-medium text-neutral-800">E-mail operacional:</dt>
                                                <dd class="truncate">{{ $affiliation->email }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </x-card>
                        </label>
                    @endforeach
                </div>

                <x-error name="affiliation_id" />

                <div class="flex justify-end">
                    <x-button type="submit" color="accent">
                        Continuar <x-heroicon-o-arrow-right class="size-4" />
                    </x-button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
