{{-- Navigatie tussen de reiziger-schermen. Staat in de hero, dus licht op donker. --}}
<nav class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Reisnavigatie">
    {{-- Eindreview: zonder deze link kon een goedgekeurde reiziger nooit meer bij
         het inschrijfscherm, en dus nooit inschrijven voor een tweede reis of
         wisselen tussen goedgekeurde reizen. --}}
    <a href="{{ route('traveler.registrations.index') }}"
       class="focusring bg-white/10 px-4 py-1.5 font-semibold ring-1 ring-white/25 hover:bg-white/20">
        Mijn reizen
    </a>
    @php
        $tabs = [
            ['route' => 'traveler.dashboard', 'label' => 'Dagprogramma'],
            ['route' => 'traveler.activities', 'label' => 'Activiteiten'],
            ['route' => 'traveler.my-choices', 'label' => 'Mijn keuzes & checklist'],
        ];
    @endphp
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route'], $trip) }}"
           @class([
               'focusring px-4 py-1.5 font-semibold',
               'bg-accent shadow-gloed' => request()->routeIs($tab['route']),
               'bg-white/10 ring-1 ring-white/25 hover:bg-white/20' => ! request()->routeIs($tab['route']),
           ])
           @if (request()->routeIs($tab['route'])) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
