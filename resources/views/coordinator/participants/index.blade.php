@extends('layouts.app')
@section('title', 'Deelnemers')

@section('content')
<h1 class="mb-4 text-2xl font-semibold text-brand">Deelnemers – {{ $trip->name }}</h1>
@include('coordinator._nav')

{{-- FE-08 --}}
@if ($participants->isEmpty())
    <div role="status" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-700">
        <span aria-hidden="true">&#8505;</span> <span>Nog geen deelnemers voor deze reis.</span>
    </div>
@else
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Naam</th>
                        <th class="px-4 py-3">Gekozen activiteit(en)</th>
                        <th class="px-4 py-3">Checklist afgerond</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($participants as $p)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $p->user->name }}</td>
                            <td class="px-4 py-3">
                                {{ $p->choices->isEmpty() ? '—' : $p->choices->pluck('name')->join(', ') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 rounded bg-slate-200">
                                        <div class="h-2 rounded {{ $p->checklist_percentage === 100 ? 'bg-success' : 'bg-brand' }}"
                                             style="width: {{ $p->checklist_percentage }}%"></div>
                                    </div>
                                    <span>{{ $p->checklist_percentage }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif
@endsection
