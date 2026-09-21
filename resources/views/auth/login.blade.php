@extends('layouts.app')
@section('title', 'Inloggen')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Inloggen">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
            <x-field name="password" label="Wachtwoord" type="password" required />
            <p class="text-right text-sm">
                <a href="{{ route('password.request') }}" class="text-brand underline">Wachtwoord vergeten?</a>
            </p>
            <x-button type="submit" class="w-full">Inloggen</x-button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            Nog geen account?
            <a href="{{ route('register') }}" class="font-medium text-brand underline">Registreren</a>
        </p>
    </x-card>
</div>
@endsection
