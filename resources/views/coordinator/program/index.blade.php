@extends('layouts.app')
@section('title', 'Programma beheren')
@section('kop', 'Programma')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
{{-- Doelblok voor snel.js: toevoegen en verwijderen verversen alleen dit blok. --}}
<div id="programma" tabindex="-1" class="outline-none">
    @if ($days->isEmpty())
        <x-leeg>Deze reis heeft nog geen dagen. Controleer de begin- en einddatum.</x-leeg>
    @else
        <div class="space-y-6">
            @foreach ($days as $day)
                @php
                    $bag = 'dag'.$day->id;
                    // Na een validatiefout alleen dít formulier weer invullen, niet dat van elke dag.
                    $old = fn (string $field) => (string) old('dag') === (string) $day->id ? old($field) : '';
                @endphp

                <x-card :title="ucfirst($day->date->translatedFormat('l j F'))">
                    @if ($day->programItems->isEmpty())
                        <x-leeg>Nog geen programmaonderdelen op deze dag.</x-leeg>
                    @else
                        <ol class="relative ml-2 space-y-3 border-l-2 border-brand/20 pl-6">
                            @foreach ($day->programItems as $item)
                                <li class="relative flex flex-wrap items-center gap-x-6 gap-y-2">
                                    <span aria-hidden="true" class="absolute top-1.5 -left-[33px] h-4 w-4 rounded-full border-[3px] border-white bg-accent shadow"></span>
                                    <span class="w-14 font-mono text-sm font-bold text-brand">{{ substr($item->time, 0, 5) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-semibold">{{ $item->title }}</span>
                                        @if ($item->location)
                                            <span class="block text-sm text-slate-500">{{ $item->location }}</span>
                                        @endif
                                    </span>
                                    <span class="flex gap-2">
                                        <x-button variant="secondary" size="sm" :href="route('coordinator.trips.program.edit', [$trip, $item])">Bewerken</x-button>
                                        <form method="POST" action="{{ route('coordinator.trips.program.destroy', [$trip, $item]) }}" data-snel="programma"
                                              onsubmit="return confirm('Dit programmaonderdeel verwijderen?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-button variant="danger" size="sm">Verwijderen</x-button>
                                        </form>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <form method="POST" action="{{ route('coordinator.trips.program.store', [$trip, $day]) }}" data-snel="programma"
                          class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[8rem_1fr_1fr_auto] sm:items-start">
                        @csrf
                        <input type="hidden" name="dag" value="{{ $day->id }}">
                        <x-field name="time" :id="'time-'.$day->id" label="Tijd" type="time" required :bag="$bag" :value="$old('time')" />
                        <x-field name="title" :id="'title-'.$day->id" label="Onderdeel" required :bag="$bag" :value="$old('title')" placeholder="Bijv. Stadswandeling" />
                        <x-field name="location" :id="'location-'.$day->id" label="Locatie (optioneel)" :bag="$bag" :value="$old('location')" />
                        {{-- sm:mt-6 = hoogte van een veldlabel, zodat de knop naast de velden staat, ook als er een foutmelding onder staat. --}}
                        <x-button type="submit" class="sm:mt-6">Toevoegen</x-button>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
