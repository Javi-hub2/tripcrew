@extends('layouts.app')
@section('title', 'Mijn keuzes')
@section('kop', 'Mijn keuzes & checklist')
@section('eyebrow', $trip->name)
@section('hero')
    @include('traveler._tabs')
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    {{-- FE-05a: gekozen activiteiten. Doelblok voor snel.js bij "Annuleer keuze". --}}
    <div id="keuzes" tabindex="-1" class="outline-none">
        <x-card title="Gekozen activiteiten">
            @if ($choices->isEmpty())
                <x-leeg>Je hebt nog geen activiteiten gekozen.</x-leeg>
            @else
                <ul class="space-y-3">
                    @foreach ($choices as $choice)
                        <li class="flex flex-col gap-3 rounded-xl bg-sand/60 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand font-bold text-white">
                                    {{ mb_strtoupper(mb_substr($choice->activity->name, 0, 1)) }}
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $choice->activity->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $choice->activity->tripDay->date->translatedFormat('l j F') }}</p>
                                </div>
                            </div>

                            @if ($choice->activity->deadlineHasPassed())
                                <div>
                                    <x-button type="button" variant="ghost" size="sm" disabled>Annuleer keuze</x-button>
                                    <p class="mt-1 text-xs text-slate-600">Annuleren kan niet meer: de deadline is voorbij.</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('choices.destroy', $choice) }}" data-snel="keuzes"
                                      onsubmit="return confirm('Weet je zeker dat je deze keuze wilt annuleren?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="danger" size="sm">Annuleer keuze</x-button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    {{-- FE-05b: checklist. Doelblok voor snel.js bij afvinken en toevoegen. --}}
    <div id="checklist" tabindex="-1" class="outline-none">
        <x-card title="Checklist">
            @php
                // De teller telt de vaste punten van de coördinator en de eigen punten samen.
                $total = $requiredItems->count() + $checklistItems->count();
                $done = count($doneRequiredIds) + $checklistItems->where('checked', true)->count();
            @endphp

            @if ($total === 0)
                <p class="mb-4 text-sm text-slate-600">Nog geen checklist-items.</p>
            @else
                <p class="mb-1 text-sm text-slate-600">{{ $done }} van {{ $total }} afgevinkt</p>
                <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-200" aria-hidden="true">
                    <div class="h-full rounded-full bg-success transition-all duration-500" style="width: {{ round($done / $total * 100) }}%"></div>
                </div>
            @endif

            @if ($requiredItems->isNotEmpty())
                <h3 class="mb-1 text-xs font-semibold tracking-wider text-slate-500 uppercase">Van de coördinator</h3>
                <ul class="mb-4 space-y-1">
                    @foreach ($requiredItems as $item)
                        <li>
                            <form method="POST" action="{{ route('traveler.required-checklist.toggle', [$trip, $item]) }}" data-snel="checklist">
                                @csrf
                                @method('PATCH')
                                @include('traveler._vinkje', ['checked' => in_array($item->id, $doneRequiredIds), 'label' => $item->label])
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($checklistItems->isNotEmpty())
                @if ($requiredItems->isNotEmpty())
                    <h3 class="mb-1 text-xs font-semibold tracking-wider text-slate-500 uppercase">Eigen punten</h3>
                @endif
                <ul class="mb-4 space-y-1">
                    @foreach ($checklistItems as $item)
                        <li>
                            <form method="POST" action="{{ route('checklist.toggle', $item) }}" data-snel="checklist">
                                @csrf
                                @method('PATCH')
                                @include('traveler._vinkje', ['checked' => $item->checked, 'label' => $item->label])
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('traveler.checklist.store', $trip) }}" data-snel="checklist" class="flex items-start gap-2">
                @csrf
                <label for="label" class="sr-only">Nieuw checklist-item</label>
                <input id="label" name="label" value="{{ old('label') }}" placeholder="Bijv. zonnebrand mee" required
                       class="veld mt-0 flex-1 text-sm">
                <x-button type="submit">Toevoegen</x-button>
            </form>
            @error('label')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
        </x-card>
    </div>
</div>
@endsection
