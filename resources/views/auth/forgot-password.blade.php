@extends('layouts.app')
@section('title', 'Wachtwoord vergeten')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Wachtwoord vergeten">
        <p class="mb-4 text-sm text-slate-600">
            Vul je e-mailadres in. Je ontvangt een link om een nieuw wachtwoord in te stellen.
        </p>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
            <x-button type="submit" class="w-full">Stuur mij een link</x-button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-medium text-brand underline">Terug naar inloggen</a>
        </p>
    </x-card>
</div>
@endsection
