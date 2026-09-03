<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-neutral-900 antialiased">
    <x-sidebar />

    <main class="p-3 pb-12 sm:p-6 lg:ml-64 lg:px-8 lg:pt-8">{{ $slot }}</main>

    <x-toast />
</body>
</html>
