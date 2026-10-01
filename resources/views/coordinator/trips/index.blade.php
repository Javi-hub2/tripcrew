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

                {{-- Voortgangsdashboard: status altijd als tekst, de balk is extra (FE-09). --}}
                @php
                    $checklistPct = $trip->travelers_count ? (int) round($trip->checklists_done / $trip->travelers_count * 100) : 0;
                    $seatsPct = $trip->seats_total ? min(100, (int) round($trip->seats_taken / $trip->seats_total * 100)) : 100;
                @endphp
                <dl class="space-y-4 px-5 pt-5 text-sm">
                    <div>
                        <dt class="font-semibold text-brand-darker">Checklists</dt>
                        @if ($trip->travelers_count === 0)
                            <dd class="text-slate-500">Nog geen deelnemers</dd>
                        @else
                            <dd class="text-slate-600">{{ $trip->checklists_done }} van {{ $trip->travelers_count }} deelnemers klaar</dd>
                            <dd class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200"
                                role="progressbar" aria-valuenow="{{ $trip->checklists_done }}" aria-valuemin="0" aria-valuemax="{{ $trip->travelers_count }}"
                                aria-label="Checklists klaar voor {{ $trip->name }}">
                                <div class="balk-vul h-full rounded-full {{ $checklistPct === 100 ? 'bg-success' : 'bg-brand' }}" style="width: {{ $checklistPct }}%"></div>
                            </dd>
                        @endif
                    </div>
                    <div>
                        <dt class="font-semibold text-brand-darker">Activiteiten</dt>
                        @if ($trip->activities->isEmpty())
                            <dd class="text-slate-500">Nog geen activiteiten</dd>
                        @else
                            <dd class="text-slate-600">
                                {{ $trip->seats_taken }} van {{ $trip->seats_total }} plekken bezet
                                @if ($trip->activities_full)
                                    · <span class="font-semibold text-accent-dark">{{ $trip->activities_full }} {{ $trip->activities_full === 1 ? 'activiteit' : 'activiteiten' }} vol</span>
                                @endif
                            </dd>
                            <dd class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200"
                                role="progressbar" aria-valuenow="{{ $trip->seats_taken }}" aria-valuemin="0" aria-valuemax="{{ $trip->seats_total }}"
                                aria-label="Bezetting activiteiten {{ $trip->name }}">
                                <div class="balk-vul h-full rounded-full {{ $seatsPct >= 75 ? 'bg-accent' : 'bg-brand' }}" style="width: {{ $seatsPct }}%"></div>
                            </dd>
                        @endif
                    </div>
                    @if ($trip->pending_registrations_count)
                        <div>
                            <dt class="sr-only">Aanvragen</dt>
                            <dd>
                                <a href="{{ route('coordinator.registrations.index') }}" class="focusring inline-block rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent-dark hover:bg-accent/20">
                                    {{ $trip->pending_registrations_count }} {{ $trip->pending_registrations_count === 1 ? 'aanvraag' : 'aanvragen' }} open
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>

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
