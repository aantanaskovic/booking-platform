@php
    $isAuthenticated = auth()->check();
    $isVerified = $isAuthenticated && auth()->user()->hasVerifiedEmail();
@endphp

<header class="sticky top-0 z-30 border-b border-gray-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold tracking-tight text-gray-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                B
            </span>
            {{ config('app.name', 'Bookwise') }}
        </a>

        <nav class="hidden items-center gap-6 md:flex">
            @if ($isAuthenticated)
                @if ($isVerified)
                    <a href="{{ route('dashboard') }}"
                        class="text-sm font-medium text-gray-700 hover:text-indigo-600">Dashboard</a>
                    <a href="{{ route('customers.index') }}"
                        class="text-sm font-medium text-gray-700 hover:text-indigo-600">Customers</a>
                    <a href="{{ route('services.index') }}"
                        class="text-sm font-medium text-gray-700 hover:text-indigo-600">Services</a>
                    <a href="{{ route('bookings.index') }}"
                        class="text-sm font-medium text-gray-700 hover:text-indigo-600">Bookings</a>
                @else
                    <a href="{{ route('verification.notice') }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Verify email</a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-indigo-600">Logout</button>
                </form>
            @else
                <a href="{{ route('home') }}#features"
                    class="text-sm font-medium text-gray-600 hover:text-indigo-600">Features</a>
                <a href="{{ route('home') }}#how-it-works"
                    class="text-sm font-medium text-gray-600 hover:text-indigo-600">How it works</a>
                <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-indigo-600">Log
                    in</a>
                <a href="{{ route('register') }}"
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Get
                    started</a>
            @endif
        </nav>

        <button type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 md:hidden"
            aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-navigation" data-menu-toggle>
            <svg data-menu-icon-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg data-menu-icon-close xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <nav id="mobile-navigation" class="hidden border-t border-gray-200 bg-white px-4 py-4 md:hidden">
        <div class="mx-auto flex max-w-7xl flex-col gap-1">
            @if ($isAuthenticated)
                @if ($isVerified)
                    <a href="{{ route('dashboard') }}"
                        class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <a href="{{ route('customers.index') }}"
                        class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Customers</a>
                    <a href="{{ route('services.index') }}"
                        class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Services</a>
                    <a href="{{ route('bookings.index') }}"
                        class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Bookings</a>
                @else
                    <a href="{{ route('verification.notice') }}"
                        class="rounded-lg px-3 py-3 text-sm font-medium text-indigo-600 hover:bg-gray-50">Verify
                        email</a>
                @endif

                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 pt-2">
                    @csrf
                    <button type="submit"
                        class="w-full cursor-pointer rounded-lg px-3 py-3 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50">Logout</button>
                </form>
            @else
                <a href="{{ route('home') }}#features"
                    class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Features</a>
                <a href="{{ route('home') }}#how-it-works"
                    class="rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">How it works</a>
                <a href="{{ route('login') }}"
                    class="rounded-lg px-3 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Log in</a>
                <a href="{{ route('register') }}"
                    class="mt-2 rounded-lg bg-indigo-600 px-3 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700">Get
                    started</a>
            @endif
        </div>
    </nav>
</header>
