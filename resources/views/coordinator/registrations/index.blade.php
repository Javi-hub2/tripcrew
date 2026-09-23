@extends('layouts.app')
@section('title', 'Openstaande aanvragen')
@php $pending = $trips->sum(fn ($trip) => $trip->pendingRegistrations->count()); @endphp
@section('subtitle', $pending === 0 ? 'Alles is afgehandeld.' : ($pending === 1 ? '1 reiziger wacht op je besluit.' : "{$pending} reizigers wachten op je besluit."))

@section('content')
{{-- Dit scherm gaat over alle reizen tegelijk, dus de _nav-partial (die een $trip verwacht) wordt hier niet gebruikt.
     Doelblok voor snel.js: na goedkeuren of afwijzen wordt alleen dit blok ververst. --}}
<div id="aanvragen" tabindex="-1" class="outline-none">
    @if ($trips->isEmpty())
        <x-leeg>Er staan geen aanvragen open.</x-leeg>
    @else
        <div class="space-y-8">
            @foreach ($trips as $trip)
                <section>
                    <h2 class="mb-3 text-xs font-bold tracking-[0.12em] text-brand uppercase">{{ $trip->name }}</h2>
                    <ul class="space-y-3">
                        @foreach ($trip->pendingRegistrations as $traveler)
                            @php
                                $namen = preg_split('/\s+/', trim($traveler->name));
                                $initialen = mb_strtoupper(mb_substr($namen[0], 0, 1).(count($namen) > 1 ? mb_substr(end($namen), 0, 1) : ''));
                            @endphp
                            <li data-snel-rij class="flex flex-wrap items-center gap-4 rounded-2xl bg-white p-4 shadow-kaart ring-1 ring-black/5">
                                <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand font-bold text-white">{{ $initialen }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ $traveler->name }}</p>
                                    <p class="truncate text-sm text-slate-600">{{ $traveler->email }}</p>
                                    {{-- Ontwerp: naam, e-mailadres, reis en datum van de aanvraag. --}}
                                    @if ($traveler->pivot->requested_at)
                                        <p class="text-sm text-slate-500">Aangevraagd op {{ \Illuminate\Support\Carbon::parse($traveler->pivot->requested_at)->format('d-m-Y') }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('coordinator.registrations.approve', [$trip, $traveler]) }}" data-snel="aanvragen">
                                        @csrf @method('PATCH')
                                        <x-button type="submit" size="sm"><span aria-hidden="true">&#10003;</span> Goedkeuren</x-button>
                                    </form>
                                    <form method="POST" action="{{ route('coordinator.registrations.reject', [$trip, $traveler]) }}" data-snel="aanvragen">
                                        @csrf @method('PATCH')
                                        <x-button type="submit" variant="danger" size="sm"><span aria-hidden="true">&#10005;</span> Afwijzen</x-button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
