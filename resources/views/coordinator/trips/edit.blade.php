@extends('layouts.app')
@section('title', 'Reis bewerken')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-[#0C4A6E]">{{ $trip->name }} bewerken</h1>
@include('coordinator._nav')
<form method="POST" action="{{ route('coordinator.trips.update', $trip) }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">
    @method('PUT')
    @include('coordinator.trips._form')
</form>
@endsection
