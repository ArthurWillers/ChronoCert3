@props(['model'])

<x-card {{ $attributes->merge(['class' => 'p-6']) }}>
    <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-4">Metadados</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center text-sm">
            <span class="text-neutral-500">Criado em:</span>
            <span class="font-medium text-neutral-900">{{ $model->created_at ? formatDateTime($model->created_at) : '-' }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
            <span class="text-neutral-500">Atualizado:</span>
            <span class="font-medium text-neutral-900">{{ $model->updated_at ? formatDateTime($model->updated_at) : '-' }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
            <span class="text-neutral-500">ID:</span>
            <span class="font-mono text-neutral-500 bg-neutral-100 px-1.5 py-0.5 rounded text-xs">#{{ $model->id }}</span>
        </div>
    </div>
</x-card>

