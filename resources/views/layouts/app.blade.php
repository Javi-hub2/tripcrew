<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TripCrew')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-sand text-slate-800 antialiased">
    <nav class="bg-brand text-white shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-semibold">
                <span aria-hidden="true">&#9992;</span> TripCrew
            </a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    <span>{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg bg-brand-dark px-3 py-1.5 font-medium text-white hover:bg-brand-dark/80">
                            Uitloggen
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        <x-flash-message />
        @yield('content')
    </main>
</body>
</html>
