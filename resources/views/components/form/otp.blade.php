@props([
    'name' => 'otp',
    'length' => 6,
    'private' => false,
])

@php
    $classes = 'w-10 h-12 text-center text-lg font-semibold border appearance-none rounded-xl bg-white disabled:shadow-none shadow-xs focus:shadow-lg text-neutral-700 disabled:text-neutral-400 placeholder-neutral-400 disabled:placeholder-neutral-400/70 outline-none focus:border-[var(--color-accent)] focus:ring-2 focus:ring-[var(--color-accent)]/40 transition-colors duration-300 border-neutral-200';
@endphp

<div x-data="{
        length: {{ $length }},
        code: Array({{ $length }}).fill(''),
        
        handleInput(index, event) {
            const val = event.target.value;
            if (val) {
                this.code[index] = val.slice(-1);
                event.target.value = this.code[index];
                
                if (index < this.length - 1) {
                    this.$refs[`input${index + 1}`].focus();
                }
            } else {
                this.code[index] = '';
            }
            this.updateHiddenInput();
        },
        
        handleKeydown(index, event) {
            if (event.key === 'Backspace') {
                if (!this.code[index] && index > 0) {
                    this.$refs[`input${index - 1}`].focus();
                    this.code[index - 1] = '';
                    this.$refs[`input${index - 1}`].value = '';
                } else {
                    this.code[index] = '';
                }
                this.updateHiddenInput();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                this.$refs[`input${index - 1}`].focus();
            } else if (event.key === 'ArrowRight' && index < this.length - 1) {
                this.$refs[`input${index + 1}`].focus();
            }
        },
        
        handlePaste(event) {
            const pasteData = (event.clipboardData || window.clipboardData).getData('text').replace(/[^a-zA-Z0-9]/g, '');
            if (pasteData) {
                for (let i = 0; i < this.length && i < pasteData.length; i++) {
                    this.code[i] = pasteData[i];
                    this.$refs[`input${i}`].value = pasteData[i];
                }
                
                const focusIndex = Math.min(pasteData.length, this.length - 1);
                this.$refs[`input${focusIndex}`].focus();
                
                this.updateHiddenInput();
            }
        },
        
        updateHiddenInput() {
            this.$refs.hiddenInput.value = this.code.join('');
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }"
    class="flex items-center gap-2">
    
    <input type="hidden" name="{{ $name }}" x-ref="hiddenInput" />
    
    @for ($i = 0; $i < $length; $i++)
        <input 
            type="{{ $private ? 'password' : 'text' }}"
            x-ref="input{{ $i }}"
            inputmode="text"
            maxlength="2"
            autocomplete="one-time-code"
            @input="handleInput({{ $i }}, $event)"
            @keydown="handleKeydown({{ $i }}, $event)"
            @paste.prevent="handlePaste($event)"
            class="{{ $classes }}"
            {{ $attributes->whereDoesntStartWith('class') }}
        />
    @endfor
</div>

