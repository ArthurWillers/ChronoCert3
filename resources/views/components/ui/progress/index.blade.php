@props([
    'value' => 0,
    'max' => 100,
    'label' => null,
    'showValue' => true,
])

@php
    $percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($label || $showValue)
        <div class="flex justify-between items-center mb-1.5">
            @if($label)
                <span class="text-sm font-medium text-neutral-700">{{ $label }}</span>
            @endif
            
            @if($showValue)
                <span class="text-xs font-semibold text-neutral-500">{{ round($percentage) }}%</span>
            @endif
        </div>
    @endif
    
    <div class="w-full bg-neutral-200/80 rounded-full h-2 overflow-hidden shadow-inner">
        <div class="bg-[var(--color-accent)] h-full rounded-full transition-all duration-500 ease-out" 
             style="width: {{ $percentage }}%"></div>
    </div>
</div>

