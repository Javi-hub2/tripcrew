@extends('layouts.app')
@section('title', 'Reis bewerken')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">{{ $trip->name }} bewerken</h1>
@include('coordinator._nav')
<form method="POST" action="{{ route('coordinator.trips.update', $trip) }}" class="max-w-xl">
    <x-card>
        <div class="space-y-4">
            @method('PUT')
            @include('coordinator.trips._form')
        </div>
    </x-card>
</form>
@endsection
