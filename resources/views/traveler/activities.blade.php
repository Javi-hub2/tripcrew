@extends('layouts.app')
@section('title', 'Activiteiten')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">Activiteiten</h1>

@include('traveler._tabs')

@if ($trip->days->isNotEmpty())
    <div class="mb-6 flex flex-wrap gap-2 text-sm">
        @foreach ($trip->days as $d)
            <a href="{{ route('traveler.activities', [$trip, $d->id]) }}"
               @class([
                   'rounded px-3 py-1',
                   'bg-accent font-semibold text-white' => $day && $day->id === $d->id,
                   'bg-white ring-1 ring-slate-200' => ! ($day && $day->id === $d->id),
               ])>
                {{ $d->date->format('d-m') }}
            </a>
        @endforeach
    </div>
@endif

@if ($activities->isEmpty())
    <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-slate-700">
        <span aria-hidden="true">&#8505;</span>
        <span>Er zijn nog geen activiteiten voor deze dag.</span>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($activities as $activity)
            @php
                $taken = $activity->choices_count;
                $left = max(0, $activity->capacity - $taken);
                $pct = $activity->capacity ? min(100, round($taken / $activity->capacity * 100)) : 100;
                $chosen = $myChoiceActivityIds->contains($activity->id);
                $deadlinePassed = $activity->deadlineHasPassed();
                $full = $left === 0;
            @endphp

            {{-- FE-04: kaart met capaciteitsmeter, deadline en knop --}}
            <x-card class="flex flex-col">
                <h2 class="mb-2 text-lg font-semibold">{{ $activity->name }}</h2>

                <div class="mb-1 flex justify-between text-sm">
                    <span>{{ $left }} van {{ $activity->capacity }} plekken vrij</span>
                </div>
                <div class="mb-3 h-2 w-full rounded bg-slate-200"
                     role="progressbar" aria-valuenow="{{ $taken }}" aria-valuemin="0" aria-valuemax="{{ $activity->capacity }}"
                     aria-label="Bezetting {{ $activity->name }}">
                    <div class="h-2 rounded {{ $full ? 'bg-accent-dark' : 'bg-brand' }}" style="width: {{ $pct }}%"></div>
                </div>

                <p class="mb-4 text-sm text-slate-600">
                    Deadline: {{ $activity->deadline->format('d-m-Y H:i') }}
                </p>

                <div class="mt-auto">
                    @if ($chosen)
                        <p class="flex items-center gap-2 rounded border border-success/30 bg-success/10 px-3 py-2 text-sm text-success">
                            <span aria-hidden="true">&#10003;</span> Je hebt deze activiteit gekozen
                        </p>
                    @elseif ($deadlinePassed || $full)
                        <button type="button" disabled
                                class="w-full cursor-not-allowed rounded bg-slate-200 px-4 py-2 font-medium text-slate-500">
                            Kies deze activiteit
                        </button>
                        <p class="mt-2 flex items-center gap-2 text-sm text-danger">
                            <span aria-hidden="true">&#10007;</span>
                            {{ $deadlinePassed ? 'Deadline voorbij.' : 'Activiteit vol.' }}
                        </p>
                    @else
                        <form method="POST" action="{{ route('activities.choose', $activity) }}">
                            @csrf
                            <x-button type="submit" variant="primary" class="w-full">
                                Kies deze activiteit
                            </x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
