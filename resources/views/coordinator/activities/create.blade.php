@extends('layouts.app')
@section('title', 'Activiteit toevoegen')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-[#0C4A6E]">Activiteit toevoegen – {{ $trip->name }}</h1>
@if ($days->isEmpty())
    <div role="alert" class="flex items-center gap-2 rounded-lg border border-[#F97316]/40 bg-orange-50 px-4 py-3 text-orange-900">
        <span aria-hidden="true">&#9888;</span> <span>Deze reis heeft nog geen dagen. Controleer de begin- en einddatum.</span>
    </div>
@else
    <form method="POST" action="{{ route('coordinator.trips.activities.store', $trip) }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">
        @include('coordinator.activities._form')
    </form>
@endif
@endsection
