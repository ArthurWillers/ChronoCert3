<x-layouts.guest>
    <h1 class="text-xl font-bold text-neutral-900">Definir nova senha</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form-input name="email" type="email" label="E-mail de login" :value="old('email', $request->email)" required autocomplete="email" />
        <x-form-input name="password" type="password" label="Nova senha" required autocomplete="new-password" />
        <x-form-input name="password_confirmation" type="password" label="Confirme a nova senha" required autocomplete="new-password" />
        <x-button type="submit" class="w-full">Atualizar senha</x-button>
    </form>
</x-layouts.guest>
