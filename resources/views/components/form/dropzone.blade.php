@props([
    'name' => 'document',
    'multiple' => false,
    'accept' => '*',
    'label' => 'Clique para fazer upload',
    'sublabel' => 'ou arraste e solte o arquivo aqui',
])

<div x-data="{
        isDropping: false,
        defaultLabel: @js($label),
        textSwapTimer: null,
        files: [],
        setDropping(value) {
            if (this.isDropping === value) {
                return;
            }

            this.isDropping = value;
            this.swapDropzoneText(value ? 'Solte o arquivo agora' : this.defaultLabel);
        },
        swapDropzoneText(nextText) {
            const textElement = this.$refs.dropzoneText;
            const duration = parseFloat(getComputedStyle(textElement).getPropertyValue('--text-swap-dur')) || 150;

            clearTimeout(this.textSwapTimer);
            textElement.classList.remove('is-enter-start');
            textElement.classList.add('is-exit');

            this.textSwapTimer = setTimeout(() => {
                textElement.textContent = nextText;
                textElement.classList.remove('is-exit');
                textElement.classList.add('is-enter-start');
                void textElement.offsetHeight;

                requestAnimationFrame(() => {
                    textElement.classList.remove('is-enter-start');
                });
            }, duration);
        },
        handleDrop(e) {
            this.setDropping(false);
            this.files = Array.from(e.dataTransfer.files);
            $refs.fileInput.files = e.dataTransfer.files;
            $refs.fileInput.dispatchEvent(new Event('change'));
        },
        handleFileSelect(e) {
            this.files = Array.from(e.target.files);
        },
        removeFile(index) {
            this.files.splice(index, 1);
            
            // Reconstruct DataTransfer to update input.files
            const dt = new DataTransfer();
            this.files.forEach(file => dt.items.add(file));
            $refs.fileInput.files = dt.files;
            $refs.fileInput.dispatchEvent(new Event('change'));
        }
    }"
    class="w-full">
    
    <label 
        @dragover.prevent="setDropping(true)"
        @dragleave.prevent="setDropping(false)"
        @drop.prevent="handleDrop($event)"
        :class="isDropping ? 'border-[var(--color-accent)] bg-[var(--color-accent)]/5' : 'border-neutral-300 hover:bg-neutral-50'"
        class="flex h-32 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed transition motion-ease-smooth-out motion-duration-quick">
        
        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
            <x-heroicon-o-cloud-arrow-up
                class="mb-3 h-8 w-8"
                x-bind:class="isDropping ? 'scale-110 -translate-y-0.5 text-[var(--color-accent)]' : 'scale-100 translate-y-0 text-neutral-500'"
                aria-hidden="true"
            />
            <p class="mb-1 text-sm text-neutral-500">
                <span x-ref="dropzoneText" class="t-text-swap font-semibold">{{ $label }}</span>
            </p>
            <p
                class="text-xs text-neutral-500"
                x-show="!isDropping"
                x-transition:enter="transition ease-in-out motion-duration-quick"
                x-transition:enter-start="translate-y-1 blur-sm opacity-0"
                x-transition:enter-end="translate-y-0 blur-0 opacity-100"
                x-transition:leave="transition ease-in-out motion-duration-quick"
                x-transition:leave-start="translate-y-0 blur-0 opacity-100"
                x-transition:leave-end="-translate-y-1 blur-sm opacity-0"
            >{{ $sublabel }}</p>
        </div>
        
        <input x-ref="fileInput" @change="handleFileSelect" id="{{ $name }}" name="{{ $name }}" type="file" class="hidden" {{ $multiple ? 'multiple' : '' }} accept="{{ $accept }}" {{ $attributes }} />
    </label>

    {{-- File List --}}
    <template x-if="files.length > 0">
        <ul class="mt-3 space-y-2">
            <template x-for="(file, index) in files" :key="index">
                <li class="flex items-center justify-between p-2 text-sm bg-neutral-100 rounded-md border border-neutral-200">
                    <div class="flex items-center space-x-2 overflow-hidden">
                        <x-heroicon-o-document class="w-4 h-4 text-neutral-500 shrink-0" />
                        <span class="truncate font-medium text-neutral-700" x-text="file.name"></span>
                        <span class="text-xs text-neutral-500 shrink-0" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></span>
                    </div>
                    <button @click.prevent="removeFile(index)" type="button" class="p-1 text-neutral-400 hover:text-red-500 transition-colors">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </li>
            </template>
        </ul>
    </template>
</div>

<x-error :name="$name" />
