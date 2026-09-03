<x-layouts.guest>
    <h1 class="text-xl font-bold text-neutral-900">Redefinir senha</h1>
    <p class="mt-2 text-sm text-neutral-500">Enviaremos um link para o e-mail de login da sua conta.</p>
    @if(session('status'))<div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <x-form-input name="email" type="email" label="E-mail de login" required autofocus autocomplete="email" />
        <x-button type="submit" class="w-full">Enviar link de redefinição</x-button>
    </form>
    <a href="{{ route('login') }}" class="mt-5 inline-flex text-sm font-semibold text-accent hover:underline">Voltar ao login</a>
</x-layouts.guest>
