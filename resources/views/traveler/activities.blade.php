@extends('layouts.app')
@section('title', 'Activiteiten')
@section('kop', 'Kies je activiteiten')
@section('eyebrow', $trip->name)
@section('hero')
    @include('traveler._tabs')
@endsection

@section('content')
@if ($trip->days->isNotEmpty())
    <nav class="mb-6 flex flex-wrap gap-2 text-sm" aria-label="Dagen">
        @foreach ($trip->days as $d)
            @php $active = $day && $day->id === $d->id; @endphp
            <a href="{{ route('traveler.activities', [$trip, $d->id]) }}"
               @class([
                   'rounded-full px-4 py-1.5 font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2',
                   'bg-accent text-white shadow-gloed' => $active,
                   'bg-white text-brand ring-1 ring-brand/20 hover:bg-brand/5' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                {{ $d->date->translatedFormat('D j M') }}
            </a>
        @endforeach
    </nav>
@endif

{{-- Doelblok voor snel.js: na "Kies" wordt alleen dit blok ververst. --}}
<div id="activiteiten" tabindex="-1" class="outline-none">
    @if ($activities->isEmpty())
        <x-leeg>Er zijn nog geen activiteiten voor deze dag.</x-leeg>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($activities as $activity)
                @php
                    $chosen = $myChoiceActivityIds->contains($activity->id);
                    $deadlinePassed = $activity->deadlineHasPassed();
                    $full = $activity->choices_count >= $activity->capacity;
                @endphp

                <x-activity-card :activity="$activity">
                    <x-slot:acties>
                        @if ($chosen)
                            <p class="flex items-center justify-center gap-2 rounded-xl bg-success/10 px-3 py-2.5 text-sm font-semibold text-success">
                                <span aria-hidden="true">&#10003;</span> Je hebt deze activiteit gekozen
                            </p>
                        @elseif ($deadlinePassed || $full)
                            <x-button type="button" variant="ghost" class="w-full" disabled>Kies deze activiteit</x-button>
                            <p class="mt-2 flex items-center gap-2 text-sm text-danger">
                                <span aria-hidden="true">&#10007;</span>
                                {{ $deadlinePassed ? 'Deadline voorbij.' : 'Activiteit vol.' }}
                            </p>
                        @else
                            <form method="POST" action="{{ route('activities.choose', $activity) }}" data-snel="activiteiten">
                                @csrf
                                <x-button type="submit" class="w-full">Kies deze activiteit</x-button>
                            </form>
                        @endif
                    </x-slot:acties>
                </x-activity-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
