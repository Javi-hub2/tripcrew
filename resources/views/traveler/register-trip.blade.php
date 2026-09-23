@extends('layouts.app')
@section('title', 'Inschrijven voor een reis')
@section('kop', 'Mijn reizen')
@section('subtitle', 'Schrijf je in voor een reis. De coördinator beoordeelt je aanvraag; daarna zie je het dagprogramma.')

@section('content')
{{-- Doelblok voor snel.js: na "Inschrijven" wordt alleen dit blok ververst. --}}
<div id="reizen" tabindex="-1" class="outline-none">
    @if ($trips->isEmpty())
        <x-leeg>Er staan op dit moment geen reizen open. Kom later terug.</x-leeg>
    @else
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach ($trips as $trip)
                @php $status = $registrations[$trip->id] ?? null; @endphp
                <article class="overflow-hidden rounded-2xl bg-white shadow-kaart ring-1 ring-black/5 transition hover:-translate-y-1">
                    <div class="bg-linear-to-r from-brand-darker to-brand px-5 py-4 text-white">
                        <p class="text-xs font-semibold tracking-[0.12em] text-white/80 uppercase">
                            {{ $trip->start_date->format('d-m-Y') }} t/m {{ $trip->end_date->format('d-m-Y') }}
                        </p>
                        <h2 class="text-xl font-bold">{{ $trip->name }}</h2>
                    </div>

                    <div class="p-5">
                        @if ($status === \App\Enums\RegistrationStatus::Approved->value)
                            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">
                                <span aria-hidden="true">&#10003;</span> Goedgekeurd
                            </p>
                            <div><x-button variant="secondary" :href="route('traveler.dashboard', $trip)">Bekijk het programma</x-button></div>
                        @elseif ($status === \App\Enums\RegistrationStatus::Pending->value)
                            <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-3 py-1 text-sm font-semibold text-accent-dark">
                                <span aria-hidden="true">&#8987;</span> In behandeling bij de coördinator.
                            </p>
                        @else
                            @if ($status === \App\Enums\RegistrationStatus::Rejected->value)
                                <p class="mb-3 text-sm text-danger">Je vorige aanvraag is afgewezen. Je mag het opnieuw proberen.</p>
                            @endif
                            <form method="POST" action="{{ route('traveler.registrations.store', $trip) }}" data-snel="reizen">
                                @csrf
                                <x-button type="submit">Inschrijven</x-button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
