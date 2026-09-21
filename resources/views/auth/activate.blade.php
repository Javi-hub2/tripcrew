@extends('layouts.app')
@section('title', 'Account activeren')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Account activeren">
        <p class="mb-4 text-sm text-slate-600">Stel een wachtwoord in om je account te activeren.</p>

        <form method="POST" action="{{ route('activation.activate', $token) }}" class="space-y-4">
            @csrf
            <x-field name="password" label="Nieuw wachtwoord" type="password" required minlength="8" />
            <x-field name="password_confirmation" label="Herhaal wachtwoord" type="password" required minlength="8" />
            <x-button type="submit" class="w-full">Activeren en inloggen</x-button>
        </form>
    </x-card>
</div>
@endsection
