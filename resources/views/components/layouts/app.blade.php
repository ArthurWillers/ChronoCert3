<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen overflow-x-hidden bg-neutral-50 font-sans text-neutral-900 antialiased">
    <x-sidebar />

    <main class="min-w-0 p-3 pb-10 sm:p-5 lg:ml-64 lg:px-8 lg:pt-7">{{ $slot }}</main>

    <x-toast />
</body>
</html>
