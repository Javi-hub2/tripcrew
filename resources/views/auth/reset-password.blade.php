@extends('layouts.auth')
@section('title', 'Nieuw wachtwoord')

@section('content')
<x-card title="Nieuw wachtwoord instellen">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="E-mailadres" type="email" :value="old('email', $email)" required />
        <x-field name="password" label="Nieuw wachtwoord" type="password" required autofocus />
        <x-field name="password_confirmation" label="Herhaal wachtwoord" type="password" required />
        <x-button type="submit" class="w-full">Wachtwoord opslaan</x-button>
    </form>
</x-card>
@endsection
