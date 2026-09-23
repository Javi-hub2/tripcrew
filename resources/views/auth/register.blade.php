@extends('layouts.auth')
@section('title', 'Registreren')

@section('content')
<x-card title="Account aanmaken">
    <p class="mb-4 text-sm text-slate-600">
        Je ontvangt een e-mail waarmee je zelf je wachtwoord instelt.
    </p>
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <x-field name="name" label="Naam" :value="old('name')" required autofocus />
        <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required />
        <x-button type="submit" class="w-full">Account aanmaken</x-button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-600">
        Heb je al een account?
        <a href="{{ route('login') }}" class="font-semibold text-brand underline underline-offset-2 hover:text-brand-darker">Inloggen</a>
    </p>
</x-card>
@endsection
