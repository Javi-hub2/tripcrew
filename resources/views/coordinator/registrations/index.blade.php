@extends('layouts.app')
@section('title', 'Openstaande aanvragen')

@section('content')
{{-- Dit scherm gaat over alle reizen tegelijk, dus de _nav-partial (die een $trip verwacht) wordt hier niet gebruikt. --}}
<h1 class="mb-6 text-2xl font-semibold text-brand">Openstaande aanvragen</h1>

@if ($trips->isEmpty())
    <x-card>
        <p class="text-slate-600">Er staan geen aanvragen open.</p>
    </x-card>
@else
    <div class="space-y-6">
        @foreach ($trips as $trip)
            <x-card :title="$trip->name">
                <ul class="divide-y divide-slate-100">
                    @foreach ($trip->pendingRegistrations as $traveler)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium">{{ $traveler->name }}</p>
                                <p class="text-sm text-slate-600">{{ $traveler->email }}</p>
                                {{-- Ontwerp: naam, e-mailadres, reis en datum van de aanvraag. --}}
                                @if ($traveler->pivot->requested_at)
                                    <p class="text-sm text-slate-500">Aangevraagd op {{ \Illuminate\Support\Carbon::parse($traveler->pivot->requested_at)->format('d-m-Y') }}</p>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('coordinator.registrations.approve', [$trip, $traveler]) }}">
                                    @csrf @method('PATCH')
                                    <x-button type="submit">Goedkeuren</x-button>
                                </form>
                                <form method="POST" action="{{ route('coordinator.registrations.reject', [$trip, $traveler]) }}">
                                    @csrf @method('PATCH')
                                    <x-button type="submit" variant="danger">Afwijzen</x-button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
