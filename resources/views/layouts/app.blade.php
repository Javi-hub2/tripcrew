<!DOCTYPE html>
<html lang="nl">
@include('layouts._head')
<body class="min-h-screen bg-sand font-sans text-slate-800 antialiased">
    {{--
        De kop van elke pagina komt uit secties:
        - 'title'    paginatitel (ook de kop, tenzij 'kop' gezet is)
        - 'kop'      afwijkende kop
        - 'eyebrow'  bovenregel, bijv. reisnaam of periode
        - 'subtitle' regel onder de kop
        - 'hero'     extra inhoud in de kop, zoals tabbladen of knoppen
    --}}
    <x-hero>
        <x-slot:nav>
            <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Hoofdmenu">
                <a href="{{ route('dashboard') }}" class="focusring flex items-center gap-2 px-1 text-lg font-bold">
                    <span aria-hidden="true">&#9992;</span> TripCrew
                </a>
                @auth
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @if (Auth::user()->isCoordinator())
                            @foreach ([['coordinator.trips.*', 'coordinator.trips.index', 'Reizen'], ['coordinator.registrations.*', 'coordinator.registrations.index', 'Aanvragen']] as [$patroon, $route, $label])
                                <a href="{{ route($route) }}"
                                   @class([
                                       'focusring px-3 py-1.5 font-semibold',
                                       'bg-white/20' => request()->routeIs($patroon),
                                       'hover:bg-white/10' => ! request()->routeIs($patroon),
                                   ])
                                   @if (request()->routeIs($patroon)) aria-current="page" @endif>{{ $label }}</a>
                            @endforeach
                        @endif
                        <span class="hidden px-2 text-white/80 sm:inline">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="focusring bg-white/10 px-3 py-1.5 font-semibold ring-1 ring-white/25 hover:bg-white/20">
                                Uitloggen
                            </button>
                        </form>
                    </div>
                @endauth
            </nav>
        </x-slot:nav>

        <div class="mt-8">
            @hasSection('eyebrow')
                <p class="text-xs font-semibold tracking-[0.14em] text-white/80 uppercase">@yield('eyebrow')</p>
            @endif
            <h1 class="text-3xl leading-tight font-bold sm:text-4xl">
                @hasSection('kop') @yield('kop') @else @yield('title', 'TripCrew') @endif
            </h1>
            @hasSection('subtitle')
                <p class="mt-1 max-w-2xl text-white/85">@yield('subtitle')</p>
            @endif
            @yield('hero')
        </div>
    </x-hero>

    <main class="relative mx-auto -mt-10 max-w-5xl px-4 pb-16">
        <div id="meldingen" aria-live="polite">
            <x-flash-message />
        </div>
        @yield('content')
    </main>
</body>
</html>
