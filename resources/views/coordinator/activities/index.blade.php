@extends('layouts.app')
@section('title', 'Activiteiten beheren')
@section('kop', 'Activiteiten')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
<div class="mb-6 flex justify-end">
    <x-button href="{{ route('coordinator.trips.activities.create', $trip) }}">+ Activiteit toevoegen</x-button>
</div>

@if ($activities->isEmpty())
    <x-leeg>Nog geen activiteiten voor deze reis.</x-leeg>
@else
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($activities as $activity)
            <x-activity-card :activity="$activity">
                <x-slot:meta>
                    <p class="text-sm font-semibold text-accent-dark">{{ $activity->tripDay->date->translatedFormat('l j F') }}</p>
                </x-slot:meta>
                <x-slot:acties>
                    <div class="flex gap-2">
                        <x-button variant="secondary" size="sm" class="flex-1"
                                  href="{{ route('coordinator.trips.activities.edit', [$trip, $activity]) }}">Bewerken</x-button>
                        <form method="POST" action="{{ route('coordinator.trips.activities.destroy', [$trip, $activity]) }}" class="flex-1"
                              onsubmit="return confirm('Activiteit verwijderen? Bestaande keuzes vervallen ook.')">
                            @csrf
                            @method('DELETE')
                            <x-button variant="danger" size="sm" class="w-full">Verwijderen</x-button>
                        </form>
                    </div>
                </x-slot:acties>
            </x-activity-card>
        @endforeach
    </div>
@endif
@endsection
