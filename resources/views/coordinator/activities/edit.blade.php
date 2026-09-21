@extends('layouts.app')
@section('title', 'Activiteit bewerken')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">{{ $activity->name }} bewerken</h1>
<form method="POST" action="{{ route('coordinator.trips.activities.update', [$trip, $activity]) }}" class="max-w-xl">
    <x-card>
        <div class="space-y-4">
            @method('PUT')
            @include('coordinator.activities._form')
        </div>
    </x-card>
</form>
@endsection
