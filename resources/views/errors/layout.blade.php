<!DOCTYPE html>
<html lang="nl">
{{--
    Foutpagina's. Bewust zonder Auth, sessie of database: een 500 kan juist door de
    database komen, en dan mag deze pagina zelf niet ook stuklopen.
--}}
@include('layouts._head')
<body class="min-h-screen bg-sand font-sans text-slate-800 antialiased">
    <x-hero>
        <div class="pt-6 pb-10 text-center">
            <a href="{{ url('/') }}" class="focusring px-2 text-3xl font-bold">
                <span aria-hidden="true">&#9992;</span> TripCrew
            </a>
        </div>
    </x-hero>

    <main class="relative mx-auto -mt-20 max-w-md px-4 pb-16">
        <x-card class="text-center">
            <p class="text-sm font-bold tracking-[0.14em] text-accent-dark uppercase">Fout @yield('code')</p>
            <h1 class="mt-1 text-2xl font-bold text-brand-darker">@yield('title')</h1>
            <p class="mt-3 text-slate-600">@yield('message')</p>
            <div class="mt-6">
                <x-button :href="url('/')">Naar het begin</x-button>
            </div>
        </x-card>
    </main>
</body>
</html>
