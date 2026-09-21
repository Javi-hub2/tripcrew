<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /** FE-02: reiziger komt bij zijn eigen reis; coördinator bij het reizenoverzicht. */
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->isCoordinator()) {
            return redirect()->route('coordinator.trips.index');
        }

        $trip = $user->approvedTrips()->first();

        if (! $trip) {
            return redirect()->route('login')
                ->with('error', 'Je bent nog niet aan een reis gekoppeld. Neem contact op met je reiscoördinator.');
        }

        return redirect()->route('traveler.dashboard', $trip);
    }
}
