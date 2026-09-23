@extends('layouts.app')
@section('title', 'Nieuwe reis')

@section('content')
<form method="POST" action="{{ route('coordinator.trips.store') }}" class="max-w-2xl">
    <x-card>
        <div class="space-y-4">
            @include('coordinator.trips._form')
        </div>
    </x-card>
</form>
@endsection
