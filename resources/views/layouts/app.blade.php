<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TripCrew')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F8FAFC] text-slate-800">
    <nav class="bg-[#0C4A6E] text-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold">TripCrew</a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    <span>{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded bg-[#06B6D4] px-3 py-1.5 font-medium text-[#0C4A6E] hover:opacity-90">
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
