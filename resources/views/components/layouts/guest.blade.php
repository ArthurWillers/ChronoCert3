<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-screen flex-col bg-neutral-50 font-sans text-neutral-900 antialiased">
    <main class="flex flex-1 items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-lg">
            <a href="{{ url('/') }}" class="mb-8 flex items-center justify-center gap-3 text-xl font-bold text-accent">
                <span class="flex size-11 items-center justify-center rounded-xl bg-neutral-800 shadow-sm">
                    <x-layout.app-logo-icon class="w-8 h-8 shrink-0" />
                </span>
                ChronoCert
            </a>
            <x-card class="p-6 sm:p-8">
                {{ $slot }}
            </x-card>
        </div>
    </main>
</body>
</html>
