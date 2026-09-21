<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

// FE-01: reiziger kan pas inloggen nadat hij zelf een wachtwoord heeft ingesteld.
class ActivationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $user = User::where('activation_token', $token)->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Deze activatielink is ongeldig of al gebruikt.');
        }

        return view('auth.activate', ['token' => $token]);
    }

    public function activate(ActivateAccountRequest $request, string $token): RedirectResponse
    {
        $user = User::where('activation_token', $token)->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Deze activatielink is ongeldig of al gebruikt.');
        }

        // Foutgeval "wachtwoorden komen niet overeen" wordt al afgehandeld door
        // de `confirmed` validatieregel in ActivateAccountRequest — niets wordt
        // opgeslagen als de validatie faalt.
        $user->update([
            'password' => Hash::make($request->validated()['password']),
            'activated_at' => now(),
            'activation_token' => null,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Je account is geactiveerd. Welkom!');
    }
}
