@extends('layouts.app')
@section('title', 'Deelnemers')
@section('eyebrow', $trip->name)
@section('hero')
    @include('coordinator._nav')
@endsection

@section('content')
{{-- FE-08 --}}
@if ($participants->isEmpty())
    <x-leeg>Nog geen deelnemers voor deze reis.</x-leeg>
@else
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Naam</th>
                        <th class="px-4 py-3">Gekozen activiteit(en)</th>
                        <th class="px-4 py-3">Checklist afgerond</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($participants as $p)
                        <tr class="transition hover:bg-sand/60">
                            <td class="px-4 py-3 font-semibold">{{ $p->user->name }}</td>
                            <td class="px-4 py-3">
                                @if ($p->choices->isEmpty())
                                    —
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($p->choices as $choice)
                                            <span class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand">{{ $choice->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200">
                                        <div class="balk-vul h-full rounded-full {{ $p->checklist_percentage === 100 ? 'bg-success' : 'bg-brand' }}"
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
