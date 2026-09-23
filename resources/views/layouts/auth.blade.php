<!DOCTYPE html>
<html lang="nl">
@include('layouts._head')
<body class="min-h-screen bg-sand font-sans text-slate-800 antialiased">
    {{-- Inloggen, registreren, activeren en wachtwoordherstel: kaart die over de kop valt. --}}
    <x-hero>
        <div class="pt-6 pb-10 text-center">
            <h1>
                <a href="{{ route('login') }}" class="focusring px-2 text-3xl font-bold">
                    <span aria-hidden="true">&#9992;</span> TripCrew
                </a>
            </h1>
            <p class="mt-1 text-white/85">Samen op reis, alles op één plek</p>
        </div>
    </x-hero>

    <main class="relative mx-auto -mt-20 max-w-sm px-4 pb-16">
        <div id="meldingen" aria-live="polite">
            <x-flash-message />
        </div>
        @yield('content')
    </main>
</body>
</html>
