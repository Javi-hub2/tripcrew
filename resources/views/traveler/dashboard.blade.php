@extends('layouts.app')
@section('title', $trip->name)
@section('eyebrow', $trip->start_date->translatedFormat('j M').' – '.$trip->end_date->translatedFormat('j M Y'))
@section('hero')
    @include('traveler._tabs')
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    {{-- FE-03: dagprogramma --}}
    <x-card class="lg:col-span-2" :title="'Programma ' . ($day ? $day->date->translatedFormat('l j F') : '')">
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

    {{-- Briefing: praktische informatie. Eerst escapen (e), dan pas regeleinden naar <br>: nooit ruwe HTML. --}}
    <x-card title="Praktische informatie">
        @if (filled($trip->practical_info))
            <div class="leading-relaxed text-slate-700">{!! nl2br(e($trip->practical_info)) !!}</div>
        @else
            <x-leeg>De coördinator heeft nog geen praktische informatie toegevoegd.</x-leeg>
        @endif
    </x-card>
</div>
@endsection
