@extends('layouts.app')
@section('title', 'Programmaonderdeel bewerken')
@section('kop', $item->title.' bewerken')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
<form method="POST" action="{{ route('coordinator.trips.program.update', [$trip, $item]) }}" class="max-w-2xl">
    @csrf
    @method('PUT')
    <x-card>
        <div class="space-y-4">
            <x-field name="trip_day_id" label="Dag" type="select" required
                     :options="$days->mapWithKeys(fn ($day) => [$day->id => ucfirst($day->date->translatedFormat('l j F Y'))])"
                     :value="old('trip_day_id', $item->trip_day_id)" />
            <div class="grid gap-4 sm:grid-cols-[8rem_1fr]">
                <x-field name="time" label="Tijd" type="time" required :value="old('time', substr($item->time, 0, 5))" />
                <x-field name="title" label="Onderdeel" required :value="old('title', $item->title)" />
            </div>
            <x-field name="location" label="Locatie (optioneel)" :value="old('location', $item->location)" />
            <div class="flex flex-wrap gap-2">
                <x-button type="submit">Opslaan</x-button>
                <x-button variant="ghost" :href="route('coordinator.trips.program.index', $trip)">Annuleren</x-button>
            </div>
        </div>
    </x-card>
</form>
@endsection
