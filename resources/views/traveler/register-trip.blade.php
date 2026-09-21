@extends('layouts.app')
@section('title', 'Inschrijven voor een reis')

@section('content')
<h1 class="mb-1 text-2xl font-semibold text-brand">Schrijf je in voor een reis</h1>
<p class="mb-6 text-slate-600">De coördinator beoordeelt je aanvraag. Daarna zie je het dagprogramma.</p>

@if ($trips->isEmpty())
    <x-card>
        <p class="text-slate-600">Er staan op dit moment geen reizen open. Kom later terug.</p>
    </x-card>
@else
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($trips as $trip)
            @php $status = $registrations[$trip->id] ?? null; @endphp
            <x-card>
                <h2 class="text-lg font-semibold text-brand">{{ $trip->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $trip->start_date->format('d-m-Y') }} t/m {{ $trip->end_date->format('d-m-Y') }}
                </p>

                <div class="mt-4">
                    @if ($status === \App\Enums\RegistrationStatus::Approved->value)
                        <x-button variant="secondary" :href="route('traveler.dashboard', $trip)">Bekijk het programma</x-button>
                    @elseif ($status === \App\Enums\RegistrationStatus::Pending->value)
                        <p class="text-sm font-medium text-slate-600">In behandeling bij de coördinator.</p>
                    @else
                        @if ($status === \App\Enums\RegistrationStatus::Rejected->value)
                            <p class="mb-2 text-sm text-danger">Je vorige aanvraag is afgewezen. Je mag het opnieuw proberen.</p>
                        @endif
                        <form method="POST" action="{{ route('traveler.registrations.store', $trip) }}">
                            @csrf
                            <x-button type="submit">Inschrijven</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
