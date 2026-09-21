@extends('layouts.app')
@section('title', 'Nieuwe reis')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">Nieuwe reis</h1>
<form method="POST" action="{{ route('coordinator.trips.store') }}" class="max-w-xl">
    <x-card>
        <div class="space-y-4">
            @include('coordinator.trips._form')
        </div>
    </x-card>
</form>
@endsection
