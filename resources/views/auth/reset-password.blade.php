<x-layouts.guest>
    <h1 class="text-xl font-bold text-neutral-900">Definir nova senha</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form-input name="email" type="email" label="E-mail de login" :value="old('email', $request->email)" required readonly autocomplete="email" />
        <x-form-input name="password" type="password" label="Nova senha" required viewable autocomplete="new-password" />
        <x-form-input name="password_confirmation" type="password" label="Confirme a nova senha" required viewable autocomplete="new-password" />
        <x-password-rules />
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-button type="submit" class="w-full">Atualizar senha</x-button>
            <x-button color="outline" href="{{ route('login') }}" class="w-full">
                <x-heroicon-o-arrow-left class="size-4" />
                Voltar ao login
            </x-button>
        </div>
    </form>
</x-layouts.guest>
