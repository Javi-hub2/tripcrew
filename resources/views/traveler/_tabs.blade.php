{{-- Navigatie tussen de reiziger-schermen --}}
<nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Reisnavigatie">
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
               'bg-[#0C4A6E] text-white' => request()->routeIs($tab['route']),
               'bg-white text-[#0C4A6E] ring-1 ring-slate-200 hover:bg-slate-50' => ! request()->routeIs($tab['route']),
           ])
           @if (request()->routeIs($tab['route'])) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
