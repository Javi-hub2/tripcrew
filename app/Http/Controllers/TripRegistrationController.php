<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Reiziger schrijft zich in voor een reis; de coördinator beslist.
class TripRegistrationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('traveler.register-trip', [
            'trips' => Trip::orderBy('start_date')->get(),
            // Kolom expliciet kwalificeren: 'status' alleen zou nu toevallig werken
            // omdat trips geen kolom status heeft.
            'registrations' => $user->trips()->pluck('trip_user.status', 'trips.id'),
        ]);
    }

    public function store(Trip $trip): RedirectResponse
    {
        $user = Auth::user();
        $current = $user->trips()->whereKey($trip->id)->first();

        if ($current && $current->pivot->status !== RegistrationStatus::Rejected->value) {
            return back()->with('error', 'Je bent al ingeschreven voor deze reis.');
        }

        $attributes = [
            'status' => RegistrationStatus::Pending->value,
            'requested_at' => now(),
            'decided_at' => null,
            'decided_by' => null,
        ];

        // Een afgewezen aanvraag mag opnieuw ingediend worden.
        $current
            ? $user->trips()->updateExistingPivot($trip->id, $attributes)
            : $user->trips()->attach($trip->id, $attributes);

        return redirect()->route('traveler.registrations.index')
            ->with('success', 'Je aanvraag staat klaar voor de coördinator.');
    }
}
