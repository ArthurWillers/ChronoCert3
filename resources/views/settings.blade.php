<x-layouts.app>
    <div class="mb-6">
        <h2 class="mb-2 text-2xl font-medium">Perfil</h2>
        <p class="mt-1 text-base text-neutral-600">Gerencie seu perfil e configurações da conta.</p>
    </div>

    <div class="mx-auto max-w-2xl space-y-12 pb-5">
        <section>
            <x-section-header title="Informações do perfil" icon="heroicon-o-user" />
            <p class="mt-1 text-sm text-neutral-600">Atualize as informações de perfil da sua conta.</p>

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <x-form-input name="name" type="text" label="Nome" class="w-full" :value="auth()->user()->name" bag="updateProfileInformation" disabled help="O nome da conta não pode ser alterado por esta tela." />
                    <x-form-input name="email" type="email" label="E-mail de login" class="w-full" :value="auth()->user()->email" bag="updateProfileInformation" required autocomplete="email" />
                    <x-form-input name="cpf" type="text" label="CPF" class="w-full" :value="auth()->user()->cpf" disabled help="O CPF é o identificador único da sua conta e não pode ser alterado aqui." />
                </div>

                <div class="flex items-center gap-4">
                    <x-button type="submit">Salvar</x-button>
                    @if (session('status') === 'profile-information-updated')
                        <p class="text-sm font-medium text-green-600">Perfil atualizado com sucesso.</p>
                    @endif
                </div>
            </form>
        </section>

        <hr class="border-neutral-200" />

        <section>
            <x-section-header title="Atualizar senha" icon="heroicon-o-key" />
            <p class="mt-1 text-sm text-neutral-600">Use uma senha longa, única e difícil de adivinhar.</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <x-password-rules />

                    <x-form-input name="current_password" type="password" label="Senha atual" class="w-full" bag="updatePassword" viewable required autocomplete="current-password" />
                    <x-form-input name="password" type="password" label="Nova senha" class="w-full" bag="updatePassword" viewable required autocomplete="new-password" />
                    <x-form-input name="password_confirmation" type="password" label="Confirmar nova senha" class="w-full" bag="updatePassword" viewable required autocomplete="new-password" />
                </div>

                <div class="flex items-center gap-4">
                    <x-button type="submit">Salvar senha</x-button>
                    @if (session('status') === 'password-updated')
                        <p class="text-sm font-medium text-green-600">Senha atualizada com sucesso.</p>
                    @endif
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
