@extends('layouts.app')
@section('title', 'Checklist beheren')
@section('kop', 'Checklist')
@section('eyebrow', $trip->name)
@section('subtitle', 'Vaste punten die elke reiziger zelf afvinkt. De voltooiing zie je bij Deelnemers.')
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
{{-- Doelblok voor snel.js: toevoegen en verwijderen verversen alleen dit blok. --}}
<div id="vaste-checklist" tabindex="-1" class="outline-none">
    <x-card title="Vaste checklistpunten">
        @if ($items->isEmpty())
            <x-leeg>Nog geen vaste checklistpunten voor deze reis.</x-leeg>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($items as $item)
                    <li class="flex flex-wrap items-center gap-x-6 gap-y-2 py-3">
                        <span class="min-w-0 flex-1">
                            <span class="font-semibold">{{ $item->label }}</span>
                            <span class="block text-sm text-slate-500">{{ $item->completed_by_count }} van {{ $travelerCount }} reizigers afgevinkt</span>
                        </span>
                        <form method="POST" action="{{ route('coordinator.trips.checklist.destroy', [$trip, $item]) }}" data-snel="vaste-checklist"
                              onsubmit="return confirm('Dit checklistpunt verwijderen? Ook de vinkjes van de reizigers verdwijnen.')">
                            @csrf
                            @method('DELETE')
                            <x-button variant="danger" size="sm">Verwijderen</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('coordinator.trips.checklist.store', $trip) }}" data-snel="vaste-checklist"
              class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[1fr_auto] sm:items-start">
            @csrf
            <x-field name="label" label="Punt toevoegen" required :value="old('label')" placeholder="Bijv. paspoort gecontroleerd" />
            {{-- sm:mt-6 = hoogte van een veldlabel, zodat de knop naast het veld staat. --}}
            <x-button type="submit" class="sm:mt-6">Toevoegen</x-button>
        </form>
    </x-card>
</div>
@endsection
