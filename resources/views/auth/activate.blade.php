@extends('layouts.app')
@section('title', 'Account activeren')

@section('content')
<div class="mx-auto max-w-sm rounded-xl bg-white p-6 shadow">
    <h1 class="mb-2 text-xl font-semibold text-[#0C4A6E]">Account activeren</h1>
    <p class="mb-4 text-sm text-slate-600">Stel een wachtwoord in om je account te activeren.</p>

    <form method="POST" action="{{ route('activation.activate', $token) }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="block text-sm font-medium">Nieuw wachtwoord</label>
            <input id="password" name="password" type="password" required minlength="8"
                   class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Herhaal wachtwoord</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                   class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
        </div>

        <button type="submit" class="w-full rounded bg-[#0C4A6E] px-4 py-2 font-medium text-white hover:bg-[#0C4A6E]/90">
            Activeren en inloggen
        </button>
    </form>
</div>
@endsection
