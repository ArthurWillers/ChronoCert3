<x-layouts.app>
    <div class="mx-auto max-w-6xl space-y-6">
        <x-page-header
            title="Visão institucional"
            description="Veja a estrutura acadêmica e acesse as configurações institucionais."
        />

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <x-card href="{{ route('courses.index') }}" size="sm">
                <p class="text-sm text-neutral-600">Cursos ativos</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $metrics['active_courses'] }}</p>
            </x-card>
            <x-card href="{{ route('users.index', ['type' => 'student', 'status' => 'active']) }}" size="sm">
                <p class="text-sm text-neutral-600">Discentes ativos</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $metrics['active_students'] }}</p>
            </x-card>
            <x-card href="{{ route('users.index', ['type' => 'coordinator', 'status' => 'active']) }}" size="sm">
                <p class="text-sm text-neutral-600">Coordenações</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $metrics['active_coordinators'] }}</p>
            </x-card>
            <x-card size="sm">
                <p class="text-sm text-neutral-600">Análises pendentes</p>
                <p class="mt-2 text-2xl font-semibold text-neutral-900">{{ $metrics['pending_reviews'] }}</p>
            </x-card>
        </div>
    </div>
</x-layouts.app>
