{{-- Navigatie tussen de reiziger-schermen --}}
<nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Reisnavigatie">
    {{-- Eindreview: zonder deze link kon een goedgekeurde reiziger nooit meer bij
         het inschrijfscherm, en dus nooit inschrijven voor een tweede reis of
         wisselen tussen goedgekeurde reizen. --}}
    <a href="{{ route('traveler.registrations.index') }}"
       class="rounded-full bg-white px-4 py-1.5 font-medium text-brand ring-1 ring-slate-200 hover:bg-slate-50">
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
               'rounded-full px-4 py-1.5 font-medium',
               'bg-brand text-white' => request()->routeIs($tab['route']),
               'bg-white text-brand ring-1 ring-slate-200 hover:bg-slate-50' => ! request()->routeIs($tab['route']),
           ])
           @if (request()->routeIs($tab['route'])) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
