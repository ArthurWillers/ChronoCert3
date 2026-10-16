<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen overflow-x-hidden bg-neutral-50 font-sans text-neutral-900 antialiased">
    <a href="#main-content" class="sr-only z-50 rounded-lg bg-white p-3 font-semibold text-accent focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Pular para o conteúdo</a>
    <x-sidebar />

    @if ($currentAffiliation !== null)
        <div class="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 bg-white px-3 py-3 sm:px-5 lg:ml-64 lg:px-8" aria-label="Contexto de acesso">
            <div class="min-w-0 text-sm">
                <p class="font-semibold text-neutral-900">{{ $currentAffiliation->type->label() }} <span class="font-normal text-neutral-500">· {{ $currentAffiliation->course?->name ?? 'Atuação institucional global' }}</span></p>
                @if ($currentAffiliation->registration_number)
                    <p class="mt-0.5 text-xs text-neutral-500">Matrícula {{ $currentAffiliation->registration_number }}</p>
                @endif
            </div>
        </div>
    @endif

    <main id="main-content" tabindex="-1" class="min-w-0 p-3 pb-10 sm:p-5 lg:ml-64 lg:px-8 lg:pt-7">{{ $slot }}</main>

    <x-toast />
</body>
</html>
