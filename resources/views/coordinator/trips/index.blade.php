@extends('layouts.app')
@section('title', 'Reizen beheren')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold text-brand">Reizen</h1>
    <div class="flex flex-wrap gap-2">
        <x-button variant="secondary" href="{{ route('coordinator.registrations.index') }}">
            Openstaande aanvragen ({{ $pendingRegistrationsCount }})
        </x-button>
        <x-button variant="primary" href="{{ route('coordinator.trips.create') }}">
            + Nieuwe reis
        </x-button>
    </div>
</div>

@if ($trips->isEmpty())
    <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-700">
        <span aria-hidden="true">&#8505;</span> <span>Er zijn nog geen reizen aangemaakt.</span>
    </div>
@else
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Naam</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Deelnemers</th>
                        <th class="px-4 py-3"><span class="sr-only">Acties</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($trips as $trip)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $trip->name }}</td>
                            <td class="px-4 py-3">{{ $trip->start_date->format('d-m-Y') }} – {{ $trip->end_date->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">{{ $trip->travelers_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('coordinator.trips.activities.index', $trip) }}" class="text-brand underline">Activiteiten</a>
                                    <a href="{{ route('coordinator.trips.participants.index', $trip) }}" class="text-brand underline">Deelnemers</a>
                                    <a href="{{ route('coordinator.trips.edit', $trip) }}" class="text-brand underline">Bewerken</a>
                                    <form method="POST" action="{{ route('coordinator.trips.destroy', $trip) }}"
                                          onsubmit="return confirm('Weet je zeker dat je deze reis wilt verwijderen? Alle dagen worden ook verwijderd.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-danger underline">Verwijderen</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif
@endsection
