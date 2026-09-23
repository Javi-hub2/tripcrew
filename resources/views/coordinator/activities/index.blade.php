@extends('layouts.app')
@section('title', 'Activiteiten beheren')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold text-brand">Activiteiten – {{ $trip->name }}</h1>
    <x-button variant="primary" href="{{ route('coordinator.trips.activities.create', $trip) }}">
        + Activiteit toevoegen
    </x-button>
</div>
@include('coordinator._nav')

@if ($activities->isEmpty())
    <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-700">
        <span aria-hidden="true">&#8505;</span> <span>Nog geen activiteiten voor deze reis.</span>
    </div>
@else
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Activiteit</th>
                        <th class="px-4 py-3">Dag</th>
                        <th class="px-4 py-3">Bezetting</th>
                        <th class="px-4 py-3">Deadline</th>
                        <th class="px-4 py-3"><span class="sr-only">Acties</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($activities as $activity)
                        @php $pct = $activity->capacity ? min(100, round($activity->choices_count / $activity->capacity * 100)) : 100; @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $activity->name }}</td>
                            <td class="px-4 py-3">{{ $activity->tripDay->date->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 rounded bg-slate-200">
                                        <div class="h-2 rounded {{ $pct >= 100 ? 'bg-accent-dark' : 'bg-brand' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span>{{ $activity->choices_count }}/{{ $activity->capacity }}{{ $pct >= 100 ? ' (vol)' : '' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $activity->deadline->format('d-m-Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('coordinator.trips.activities.edit', [$trip, $activity]) }}" class="text-brand underline">Bewerken</a>
                                    <form method="POST" action="{{ route('coordinator.trips.activities.destroy', [$trip, $activity]) }}"
                                          onsubmit="return confirm('Activiteit verwijderen? Bestaande keuzes vervallen ook.')">
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
