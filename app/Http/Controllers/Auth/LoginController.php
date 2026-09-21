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

        // Generieke melding: geeft nooit prijs of het e-mailadres of het
        // wachtwoord fout was.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Onjuiste gegevens.');
        }

        // FE-01: account moet eerst via de activatielink geactiveerd zijn.
        // Zelfde generieke melding, zodat niet uitlekt dat het account bestaat.
        if (! Auth::user()->isActivated()) {
            Auth::logout();

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
