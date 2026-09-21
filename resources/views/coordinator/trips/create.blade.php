@extends('layouts.app')
@section('title', 'Nieuwe reis')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-[#0C4A6E]">Nieuwe reis</h1>
<form method="POST" action="{{ route('coordinator.trips.store') }}" class="max-w-xl space-y-4 rounded-xl bg-white p-6 shadow">
    @include('coordinator.trips._form')
</form>
@endsection
