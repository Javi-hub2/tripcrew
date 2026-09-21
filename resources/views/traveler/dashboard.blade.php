@extends('layouts.app')
@section('title', $trip->name)

@section('content')
<h1 class="mb-1 text-2xl font-semibold text-brand">{{ $trip->name }}</h1>
<p class="mb-4 text-sm text-slate-600">
    {{ $trip->start_date->format('d-m-Y') }} t/m {{ $trip->end_date->format('d-m-Y') }}
</p>

@include('traveler._tabs')

{{-- FE-03: dagprogramma --}}
<x-card :title="'Programma ' . ($day ? $day->date->translatedFormat('l j F') : '')">
    @if (! $day || $day->programItems->isEmpty())
        <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-slate-700">
            <span aria-hidden="true">&#8505;</span>
            <span>Nog geen programma voor deze dag.</span>
        </div>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($day->programItems as $item)
                <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:gap-6">
                    <span class="w-16 font-mono text-sm font-semibold text-brand">{{ substr($item->time, 0, 5) }}</span>
                    <span class="flex-1 font-medium">{{ $item->title }}</span>
                    <span class="text-sm text-slate-500">{{ $item->location }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
@endsection
