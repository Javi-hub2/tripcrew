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

        // Altijd hashen, ongeacht welk pad hierna volgt. Bcrypt is met opzet traag;
        // deden we dit alleen op het "nieuw adres"-pad, dan is dat pad meetbaar
        // langzamer dan het "bestaat al"-pad en verraadt de responstijd alsnog of
        // een adres een account heeft. Niet weghalen als "onnodig werk".
        $password = Hash::make(Str::random(40));

        // Bestaat het adres al, dan verraadt de bevestiging nooit of dat zo is — die
        // blijft in elke tak hieronder exact hetzelfde.
        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            if ($existing->isActivated()) {
                // Al een bruikbaar account: hier verandert niets t.o.v. vóór deze fix.
                // Bewust GEEN Timebox hier — zie de afweging over responstijden in het
                // fixronde-rapport: dit pad blijft het goedkoopste (alleen de
                // Hash::make hierboven), het pad hieronder ("nog niet geactiveerd")
                // en het "nieuw account"-pad blijven allebei duurder (db-write + mail).
                // Dat verschil bestond in de kern al vóór deze fix, tussen "bestaat al"
                // en "nieuw account"; deze fix verandert alleen wélke van de twee
                // "bestaat al"-subgevallen aan de goedkope kant zit.
                return redirect()->route('login')->with('success', $confirmation);
            }

            // Nooit geactiveerd: de eerdere activatiemail is mogelijk nooit aangekomen
            // en het adres mag niet voorgoed onbruikbaar blijven (CRITICAL-bevinding
            // eindreview). Nieuw token, activatiemail opnieuw versturen. Zelfde
            // bevestiging als alle andere paden.
            $existing->forceFill(['activation_token' => Str::random(64)])->save();

            Mail::to($existing->email)->send(new ActivationMail($existing));

            return redirect()->route('login')->with('success', $confirmation);
        }

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => 'reiziger', // zelfregistratie levert nooit een coördinator op
                'password' => $password, // onbruikbaar tot activatie
                'activated_at' => null,
                'activation_token' => Str::random(64),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Twee gelijktijdige registraties met hetzelfde nieuwe adres: de unique index
            // op e-mail heeft de tweede tegengehouden. Zelfde stille bevestiging als hierboven.
            return redirect()->route('login')->with('success', $confirmation);
        }

        Mail::to($user->email)->send(new ActivationMail($user));

        return redirect()->route('login')->with('success', $confirmation);
    }
}
