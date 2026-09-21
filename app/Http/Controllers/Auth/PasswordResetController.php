<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const CONFIRMATION = 'Als dit adres bij ons bekend is, ontvang je een e-mail.';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Niet-geactiveerde accounts krijgen geen herstelmail: die horen hun
        // activatielink te gebruiken. De melding blijft wel hetzelfde.
        $user = User::where('email', $data['email'])->first();

        if ($user && $user->isActivated()) {
            Password::sendResetLink($data);
        }

        return back()->with('success', self::CONFIRMATION);
    }

    public function reset(string $token, Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->with('error', 'Deze herstellink is ongeldig of verlopen. Vraag een nieuwe aan.');
        }

        return redirect()->route('login')->with('success', 'Je wachtwoord is aangepast. Log in met je nieuwe wachtwoord.');
    }
}
