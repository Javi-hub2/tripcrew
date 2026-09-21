@extends('layouts.app')
@section('title', 'Activiteit toevoegen')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">Activiteit toevoegen – {{ $trip->name }}</h1>
@if ($days->isEmpty())
    <div role="alert" class="flex items-center gap-2 rounded-lg border border-accent/40 bg-accent/10 px-4 py-3 text-accent-dark">
        <span aria-hidden="true">&#9888;</span> <span>Deze reis heeft nog geen dagen. Controleer de begin- en einddatum.</span>
    </div>
@else
    <form method="POST" action="{{ route('coordinator.trips.activities.store', $trip) }}" class="max-w-xl">
        <x-card>
            <div class="space-y-4">
                @include('coordinator.activities._form')
            </div>
        </x-card>
    </form>
@endif
@endsection
