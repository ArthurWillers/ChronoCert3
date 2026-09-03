<x-layouts.guest>
    <h1 class="text-xl font-bold text-neutral-900">Acesse sua conta</h1>
    <p class="mt-2 text-sm text-neutral-500">Use o e-mail da sua conta para entrar no ChronoCert.</p>
    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
        @csrf
        <x-form-input name="email" type="email" label="E-mail de login" required autofocus autocomplete="email" />
        <x-form-input name="password" type="password" label="Senha" required viewable autocomplete="current-password" />
        <div class="flex items-center justify-between gap-3"><x-form-checkbox name="remember" label="Lembrar de mim" /><a href="{{ route('password.request') }}" class="text-sm font-semibold text-accent hover:underline">Esqueci a senha</a></div>
        <x-button type="submit" class="w-full">Entrar <x-heroicon-o-arrow-right class="size-4" /></x-button>
    </form>
</x-layouts.guest>
