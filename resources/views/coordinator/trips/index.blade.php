@extends('layouts.app')
@section('title', 'Reizen beheren')
@section('kop', 'Reizen')
@section('hero')
    <div class="mt-5 flex flex-wrap gap-2">
        <x-button variant="secondary" href="{{ route('coordinator.registrations.index') }}">
            Openstaande aanvragen ({{ $pendingRegistrationsCount }})
        </x-button>
        <x-button href="{{ route('coordinator.trips.create') }}">+ Nieuwe reis</x-button>
    </div>
@endsection

@section('content')
@if ($trips->isEmpty())
    <x-leeg>Er zijn nog geen reizen aangemaakt.</x-leeg>
@else
    <div class="grid gap-5 sm:grid-cols-2">
        @foreach ($trips as $trip)
            <article class="overflow-hidden rounded-2xl bg-white shadow-kaart ring-1 ring-black/5 transition hover:-translate-y-1">
                <div class="flex items-start justify-between gap-3 bg-linear-to-r from-brand-darker to-brand px-5 py-4 text-white">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.12em] text-white/80 uppercase">
                            {{ $trip->start_date->format('d-m-Y') }} – {{ $trip->end_date->format('d-m-Y') }}
                        </p>
                        <h2 class="text-xl font-bold">{{ $trip->name }}</h2>
                    </div>
                    <span class="shrink-0 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">
                        {{ $trip->travelers_count }} {{ $trip->travelers_count === 1 ? 'deelnemer' : 'deelnemers' }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 p-5">
                    <x-button variant="secondary" size="sm" href="{{ route('coordinator.trips.activities.index', $trip) }}">Activiteiten</x-button>
                    <x-button variant="secondary" size="sm" href="{{ route('coordinator.trips.participants.index', $trip) }}">Deelnemers</x-button>
                    <x-button variant="secondary" size="sm" href="{{ route('coordinator.trips.edit', $trip) }}">Bewerken</x-button>
                    <form method="POST" action="{{ route('coordinator.trips.destroy', $trip) }}" class="ml-auto"
                          onsubmit="return confirm('Weet je zeker dat je deze reis wilt verwijderen? Alle dagen worden ook verwijderd.')">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" size="sm">Verwijderen</x-button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
