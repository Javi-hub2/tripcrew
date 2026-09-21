@extends('layouts.app')
@section('title', 'Inloggen')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Inloggen">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
            <x-field name="password" label="Wachtwoord" type="password" required />
            <x-button type="submit" class="w-full">Inloggen</x-button>
        </form>
    </x-card>
</div>
@endsection
