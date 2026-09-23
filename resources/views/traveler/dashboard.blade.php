@extends('layouts.app')
@section('title', $trip->name)
@section('eyebrow', $trip->start_date->translatedFormat('j M').' – '.$trip->end_date->translatedFormat('j M Y'))
@section('hero')
    @include('traveler._tabs')
@endsection

@section('content')
{{-- FE-03: dagprogramma --}}
<x-card :title="'Programma ' . ($day ? $day->date->translatedFormat('l j F') : '')">
    @if (! $day || $day->programItems->isEmpty())
        <x-leeg>Nog geen programma voor deze dag.</x-leeg>
    @else
        <ol class="relative ml-2 space-y-5 border-l-2 border-brand/20 pl-6">
            @foreach ($day->programItems as $item)
                <li class="relative">
                    <span aria-hidden="true" class="absolute top-1.5 -left-[33px] h-4 w-4 rounded-full border-[3px] border-white bg-accent shadow"></span>
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                        <span class="w-14 font-mono text-sm font-bold text-brand">{{ substr($item->time, 0, 5) }}</span>
                        <span class="flex-1 font-semibold">{{ $item->title }}</span>
                        <span class="text-sm text-slate-500">{{ $item->location }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-card>
@endsection
