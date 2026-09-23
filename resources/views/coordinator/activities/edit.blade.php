@extends('layouts.app')
@section('title', 'Activiteit bewerken')

@section('kop', $activity->name.' bewerken')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
<form method="POST" action="{{ route('coordinator.trips.activities.update', [$trip, $activity]) }}" class="max-w-2xl">
    <x-card>
        <div class="space-y-4">
            @method('PUT')
            @include('coordinator.activities._form')
        </div>
    </x-card>
</form>
@endsection
