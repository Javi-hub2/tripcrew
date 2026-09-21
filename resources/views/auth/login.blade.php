@extends('layouts.app')
@section('title', 'Inloggen')

@section('content')
<div class="mx-auto max-w-sm rounded-xl bg-white p-6 shadow">
    <h1 class="mb-4 text-xl font-semibold text-[#0C4A6E]">Inloggen</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium">E-mailadres</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Wachtwoord</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded border-slate-300 focus:border-[#06B6D4] focus:ring-[#06B6D4]">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full rounded bg-[#0C4A6E] px-4 py-2 font-medium text-white hover:bg-[#0C4A6E]/90">
            Inloggen
        </button>
    </form>
</div>
@endsection
