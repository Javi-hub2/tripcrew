@extends('layouts.auth')
@section('title', 'Inloggen')

@section('content')
<x-card title="Inloggen">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
        <x-field name="password" label="Wachtwoord" type="password" required />
        <p class="text-right text-sm">
            <a href="{{ route('password.request') }}" class="font-semibold text-brand underline underline-offset-2 hover:text-brand-darker">Wachtwoord vergeten?</a>
        </p>
        <x-button type="submit" class="w-full">Inloggen</x-button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-600">
        Nog geen account?
        <a href="{{ route('register') }}" class="font-semibold text-brand underline underline-offset-2 hover:text-brand-darker">Registreren</a>
    </p>
</x-card>
@endsection
