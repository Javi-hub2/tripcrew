<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// FE-02
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // FE-01: account moet eerst via de activatielink geactiveerd zijn. Die eis
        // zit ín de poging (attemptWhen), zodat een niet-geactiveerd account met het
        // juiste wachtwoord precies het mislukte pad volgt: binnen Laravels Timebox,
        // zonder in- en weer uitloggen. Controleerden we het pas ná attempt(), dan
        // keerde die poging als "geslaagd" meteen terug uit de Timebox en verried de
        // responstijd dat het wachtwoord klopte (eindreview punt B).
        //
        // Generieke melding: geeft nooit prijs of het e-mailadres of het
        // wachtwoord fout was, of dat het account nog niet geactiveerd is.
        if (! Auth::attemptWhen($credentials, fn ($user) => $user->isActivated(), $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Onjuiste gegevens.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Je bent uitgelogd.');
    }
}
