<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-white text-gray-900 antialiased">
    <x-navigation.header />

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 bg-gray-50">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Bookwise') }}.</p>
            <div class="flex gap-5">
                <a href="{{ route('login') }}" class="transition hover:text-indigo-600">Log in</a>
                <a href="{{ route('register') }}" class="transition hover:text-indigo-600">Create account</a>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>

</html>
