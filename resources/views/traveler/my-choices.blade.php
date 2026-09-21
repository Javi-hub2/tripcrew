@extends('layouts.app')
@section('title', 'Mijn keuzes')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-[#0C4A6E]">Mijn keuzes & checklist</h1>

@include('traveler._tabs')

<div class="grid gap-6 lg:grid-cols-2">
    {{-- FE-05a: gekozen activiteiten --}}
    <section class="rounded-xl bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold">Gekozen activiteiten</h2>

        @if ($choices->isEmpty())
            <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-slate-700">
                <span aria-hidden="true">&#8505;</span>
                <span>Je hebt nog geen activiteiten gekozen.</span>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($choices as $choice)
                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium">{{ $choice->activity->name }}</p>
                            <p class="text-sm text-slate-500">{{ $choice->activity->tripDay->date->format('d-m-Y') }}</p>
                        </div>

                        @if ($choice->activity->deadlineHasPassed())
                            <div>
                                <button type="button" disabled
                                        class="cursor-not-allowed rounded bg-slate-200 px-3 py-1.5 text-sm text-slate-500">
                                    Annuleer keuze
                                </button>
                                <p class="mt-1 text-xs text-slate-600">Annuleren kan niet meer: de deadline is voorbij.</p>
                            </div>
                        @else
                            <form method="POST" action="{{ route('choices.destroy', $choice) }}"
                                  onsubmit="return confirm('Weet je zeker dat je deze keuze wilt annuleren?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded border border-[#F97316] px-3 py-1.5 text-sm font-medium text-orange-800 hover:bg-orange-50">
                                    Annuleer keuze
                                </button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- FE-05b: checklist --}}
    <section class="rounded-xl bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold">Checklist</h2>

        @if ($checklistItems->isEmpty())
            <p class="mb-4 text-sm text-slate-600">Nog geen checklist-items.</p>
        @else
            <ul class="mb-4 space-y-2">
                @foreach ($checklistItems as $item)
                    <li>
                        <form method="POST" action="{{ route('checklist.toggle', $item) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="flex w-full items-center gap-3 rounded px-2 py-1.5 text-left hover:bg-slate-50"
                                    aria-pressed="{{ $item->checked ? 'true' : 'false' }}">
                                <span class="flex h-5 w-5 items-center justify-center rounded border {{ $item->checked ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-400' }}">
                                    @if ($item->checked)&#10003;@endif
                                </span>
                                <span class="{{ $item->checked ? 'text-slate-500 line-through' : '' }}">{{ $item->label }}</span>
                                <span class="sr-only">({{ $item->checked ? 'afgevinkt' : 'nog niet afgevinkt' }})</span>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('traveler.checklist.store', $trip) }}" class="flex gap-2">
            @csrf
            <label for="label" class="sr-only">Nieuw checklist-item</label>
            <input id="label" name="label" placeholder="Bijv. paspoort gecontroleerd" required
                   class="flex-1 rounded border-slate-300 text-sm focus:border-[#06B6D4] focus:ring-[#06B6D4]">
            <button class="rounded bg-[#0C4A6E] px-3 py-1.5 text-sm font-medium text-white">Toevoegen</button>
        </form>
        @error('label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </section>
</div>
@endsection
