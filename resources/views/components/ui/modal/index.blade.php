@props([
    'name',
    'title',
    'message' => null,
    'confirmVariant' => 'danger',
    'size' => 'md',         {{-- sm | md | lg | xl | 2xl --}}
    'hideFooter' => false,  {{-- hide the default cancel/action footer --}}
])

@php
    $maxWidth = match($size) {
        'sm'  => 'sm:max-w-sm',
        'lg'  => 'sm:max-w-2xl',
        'xl'  => 'sm:max-w-3xl',
        '2xl' => 'sm:max-w-4xl',
        default => 'sm:max-w-lg', // md
    };
    $hasIcon = in_array($confirmVariant, ['danger', 'info', 'success', 'warning']);
@endphp

<div class="contents"
     x-data="{
         open: false,
         closeModal() {
             if (! this.open) {
                 return;
             }

             this.open = false;
             window.dispatchEvent(new CustomEvent('modal-closed', { detail: @js($name) }));
         }
     }"
     x-effect="document.documentElement.classList.toggle('t-modal-open', open)"
     @modal-open.window="if ($event.detail === '{{ $name }}') open = true"
     @modal-close.window="if ($event.detail === '{{ $name }}') closeModal()"
     @keydown.escape.window="closeModal()"
>

    <template x-teleport="body">
        <div class="t-modal-scroll fixed inset-0 z-50 w-screen overflow-y-auto"
             style="display: none;"
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true"
             x-show="open">
            

            <div class="absolute inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity"
                 aria-hidden="true"
                 x-show="open"
                 x-transition:enter="motion-ease-smooth-out motion-duration-fast"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="motion-ease-smooth-out motion-duration-quick"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeModal()"></div>


            <div class="relative z-10 flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full {{ $maxWidth }}"
                     x-show="open"
                     x-transition:enter="motion-ease-smooth-out motion-duration-fast"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:motion-scale-large"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="motion-ease-smooth-out motion-duration-quick"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:motion-scale-large">
                    
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="{{ $hasIcon ? 'sm:flex sm:items-start' : '' }}">
                            @if($confirmVariant === 'danger')
                                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600" />
                                </div>
                            @elseif($confirmVariant === 'info')
                                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-heroicon-o-information-circle class="h-6 w-6 text-blue-600" />
                                </div>
                            @elseif($confirmVariant === 'success')
                                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-heroicon-o-check-circle class="h-6 w-6 text-green-600" />
                                </div>
                            @elseif($confirmVariant === 'warning')
                                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-heroicon-o-exclamation-circle class="h-6 w-6 text-amber-600" />
                                </div>
                            @endif
                            <div class="{{ $hasIcon ? 'mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left' : '' }} w-full">
                                <h3 class="text-base font-semibold leading-6 text-neutral-900 {{ $hasIcon ? '' : 'mb-4' }}" id="modal-title">
                                    {{ $title }}
                                </h3>
                                <div class="{{ $hasIcon ? 'mt-2' : '' }}">
                                    @if($message)
                                        <p class="text-sm text-neutral-500 mb-4">
                                            {{ $message }}
                                        </p>
                                    @elseif(isset($content))
                                        <div class="text-sm text-neutral-500">
                                            {{ $content }}
                                        </div>
                                    @endif
                                </div>
                                @if(!$hideFooter && !$hasIcon)
                                    {{ $slot }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if(!$hideFooter && $hasIcon)
                        <div class="bg-neutral-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <div class="sm:ml-3 sm:w-auto" @click="closeModal(); $dispatch('modal-confirm')">
                                {{ $slot }}
                            </div>
                            <x-button type="button" 
                                      color="outline"
                                      class="mt-3 w-full sm:mt-0 sm:w-auto"
                                      @click="closeModal(); $dispatch('modal-cancel')">
                                Cancelar
                            </x-button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
