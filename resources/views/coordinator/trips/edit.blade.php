@extends('layouts.app')
@section('title', 'Reis bewerken')

@section('kop', 'Reis bewerken')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
<form method="POST" action="{{ route('coordinator.trips.update', $trip) }}" class="max-w-2xl">
    <x-card>
        <div class="space-y-4">
            @method('PUT')
            @include('coordinator.trips._form')
        </div>
    </x-card>
</form>
@endsection
