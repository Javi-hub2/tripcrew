{{-- Navigatie binnen één reis. Staat in de hero, dus licht op donker. --}}
<nav class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Reisbeheer">
    @foreach ([
        ['coordinator.trips.edit', route('coordinator.trips.edit', $trip), 'Reis bewerken'],
        ['coordinator.trips.program.*', route('coordinator.trips.program.index', $trip), 'Programma'],
        ['coordinator.trips.activities.*', route('coordinator.trips.activities.index', $trip), 'Activiteiten'],
        ['coordinator.trips.checklist.*', route('coordinator.trips.checklist.index', $trip), 'Checklist'],
        ['coordinator.trips.participants.*', route('coordinator.trips.participants.index', $trip), 'Deelnemers'],
    ] as [$patroon, $url, $label])
        <a href="{{ $url }}"
           @class([
               'focusring px-4 py-1.5 font-semibold',
               'bg-accent shadow-gloed' => request()->routeIs($patroon),
               'bg-white/10 ring-1 ring-white/25 hover:bg-white/20' => ! request()->routeIs($patroon),
           ])
           @if (request()->routeIs($patroon)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
