@extends('layouts.app')
@section('title', 'Activiteit toevoegen')

@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
@if ($days->isEmpty())
    <div role="alert" class="flex items-center gap-3 rounded-2xl border-l-4 border-accent bg-white px-4 py-3 text-accent-dark shadow-kaart">
        <span aria-hidden="true" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-accent/10 font-bold">&#9888;</span>
        <span>Deze reis heeft nog geen dagen. Controleer de begin- en einddatum.</span>
    </div>
@else
    <form method="POST" action="{{ route('coordinator.trips.activities.store', $trip) }}" class="max-w-2xl">
        <x-card>
            <div class="space-y-4">
                @include('coordinator.activities._form')
            </div>
        </x-card>
    </form>
@endif
@endsection
