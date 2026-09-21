<nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Reisbeheer">
    <a href="{{ route('coordinator.trips.edit', $trip) }}" class="rounded-full bg-white px-4 py-1.5 font-medium text-brand ring-1 ring-slate-200 hover:bg-slate-50">Reis bewerken</a>
    <a href="{{ route('coordinator.trips.activities.index', $trip) }}" class="rounded-full bg-white px-4 py-1.5 font-medium text-brand ring-1 ring-slate-200 hover:bg-slate-50">Activiteiten</a>
    <a href="{{ route('coordinator.trips.participants.index', $trip) }}" class="rounded-full bg-white px-4 py-1.5 font-medium text-brand ring-1 ring-slate-200 hover:bg-slate-50">Deelnemers</a>
    <a href="{{ route('coordinator.registrations.index') }}" class="rounded-full bg-white px-4 py-1.5 font-medium text-brand ring-1 ring-slate-200 hover:bg-slate-50">Aanvragen</a>
</nav>
