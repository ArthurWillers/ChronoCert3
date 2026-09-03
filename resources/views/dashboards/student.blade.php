<x-layouts.app>
    <div class="mx-auto max-w-5xl">
        <x-page-header
            title="Visão do discente"
            description="Acompanhe suas submissões e o seu extrato de atividades complementares."
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-card>
                <div class="flex items-start gap-4">
                    <x-avatar icon="heroicon-o-academic-cap" variant="accent" size="lg" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-accent">Vínculo ativo</p>
                        <h2 class="mt-1 text-lg font-semibold text-neutral-900">Discente</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $affiliation->email }}</p>
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-section-header title="Meu percurso acadêmico" icon="heroicon-o-chart-bar" />
                <p class="text-sm leading-6 text-neutral-600">
                    Seus comprovantes enviados e o total de horas aproveitadas aparecerão nesta área.
                </p>
            </x-card>
        </div>
    </div>
</x-layouts.app>
