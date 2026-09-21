<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// FE-03: dagprogramma bekijken.
class TravelerProgramController extends Controller
{
    public function dashboard(Trip $trip): View|RedirectResponse
    {
        $this->authorize('view', $trip);

        // Standaard: laat vandaag zien, of anders de eerste dag van de reis.
        $day = $trip->days()
            ->whereDate('date', now()->toDateString())
            ->first() ?? $trip->days()->first();

        return view('traveler.dashboard', [
            'trip' => $trip,
            'day' => $day,
        ]);
    }

    public function activities(Trip $trip, $tripDay = null): View
    {
        $this->authorize('view', $trip);

        $day = $tripDay
            ? $trip->days()->findOrFail($tripDay)
            : ($trip->days()->whereDate('date', now()->toDateString())->first() ?? $trip->days()->first());

        $activities = $day
            ? $day->activities()->withCount('choices')->get()
            : collect();

        $myChoiceActivityIds = Auth::user()->activityChoices()->pluck('activity_id');

        return view('traveler.activities', [
            'trip' => $trip,
            'day' => $day,
            'activities' => $activities,
            'myChoiceActivityIds' => $myChoiceActivityIds,
        ]);
    }
}
