<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Zelfregistratie. Het wachtwoord wordt pas op het activatiescherm ingesteld (FE-01),
// zodat er nooit een wachtwoord per mail gaat.
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $confirmation = 'Bijna klaar. Check je mail om je wachtwoord in te stellen.';

        // Bestaat het adres al, dan gebeurt er niets — maar de bezoeker ziet dezelfde
        // bevestiging. Anders kan iemand via dit formulier uitvissen wie er een account heeft.
        if (User::where('email', $data['email'])->exists()) {
            return redirect()->route('login')->with('success', $confirmation);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'reiziger', // zelfregistratie levert nooit een coördinator op
            'password' => Hash::make(Str::random(40)), // onbruikbaar tot activatie
            'activated_at' => null,
            'activation_token' => Str::random(64),
        ]);

        Mail::to($user->email)->send(new ActivationMail($user));

        return redirect()->route('login')->with('success', $confirmation);
    }
}
