@extends('layouts.app')
@section('title', 'Activiteit bewerken')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-[#0C4A6E]">{{ $activity->name }} bewerken</h1>
<form method="POST" action="{{ route('coordinator.trips.activities.update', [$trip, $activity]) }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">
    @method('PUT')
    @include('coordinator.activities._form')
</form>
@endsection
