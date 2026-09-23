<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // Terug naar de pagina die de gast probeerde te openen, maar alleen als deze rol
        // die mag zien. Anders kwam een coördinator die in een browser met een oude
        // reizigerssessie inlogde meteen op een 403 uit.
        $intended = $request->session()->pull('url.intended');

        return redirect()->to(
            $intended && $this->mayVisit(Auth::user(), $intended) ? $intended : route('dashboard')
        );
    }

    /** Laat de rol-middleware (role:xxx) van de route achter deze URL deze gebruiker door? */
    private function mayVisit(User $user, string $url): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($url));
        } catch (HttpException) {
            return false; // onbekende URL of verkeerde methode
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:') && substr($middleware, 5) !== $user->role) {
                return false;
            }
        }

        return true;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Je bent uitgelogd.');
    }
}
