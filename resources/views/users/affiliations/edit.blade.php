<x-layouts.app>
    <x-page-header :title="'Editar vínculo de ' . $user->name" description="Atualize apenas os dados acadêmicos e operacionais deste vínculo.">
        <x-back-button :fallback="route('users.show', $user)" />
    </x-page-header>

    <form method="POST" action="{{ route('users.affiliations.update', [$user, $affiliation]) }}" class="mt-6 max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <x-card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form-input label="Tipo de vínculo" :value="$affiliation->type->label()" readonly />
                @if ($affiliation->type === \App\Enums\AffiliationType::Coordinator)
                    @if ($canChangeCourse)
                        <x-form-select name="course_id" label="Curso" required>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id', $affiliation->course_id) === (string) $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </x-form-select>
                    @else
                        <input type="hidden" name="course_id" value="{{ $affiliation->course_id }}">
                        <x-form-input label="Curso" :value="$affiliation->course?->name" readonly />
                    @endif
                @else
                    <x-form-input label="Curso" :value="$affiliation->course?->name ?? 'Atuação global'" readonly />
                @endif

                @if ($affiliation->type === \App\Enums\AffiliationType::Student)
                    <x-form-input name="registration_number" label="Matrícula" :value="$affiliation->registration_number" required autocomplete="off" />
                @endif

                <div class="{{ $affiliation->type === \App\Enums\AffiliationType::Student ? '' : 'sm:col-span-2' }}">
                    <x-form-input name="operational_email" type="email" label="E-mail operacional do vínculo" :value="$affiliation->email" required autocomplete="email" />
                </div>
            </div>
        </x-card>

        <div class="flex items-center justify-end gap-3">
            <x-back-button :fallback="route('users.show', $user)" text="Cancelar" />
            <x-button type="submit" color="accent"><x-heroicon-o-check class="size-4" /> Salvar alterações</x-button>
        </div>
    </form>
</x-layouts.app>
