<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripDay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// FE-03: dagprogramma bekijken.
class TravelerProgramController extends Controller
{
    public function dashboard(Trip $trip, $tripDay = null): View|RedirectResponse
    {
        $this->authorize('view', $trip);

        return view('traveler.dashboard', [
            'trip' => $trip,
            'day' => $this->selectedDay($trip, $tripDay),
        ]);
    }

    public function activities(Trip $trip, $tripDay = null): View
    {
        $this->authorize('view', $trip);

        $day = $this->selectedDay($trip, $tripDay);

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

    /** Gekozen dag, of standaard vandaag, of anders de eerste dag van de reis. */
    private function selectedDay(Trip $trip, $tripDay): ?TripDay
    {
        return $tripDay
            ? $trip->days()->findOrFail($tripDay)
            : ($trip->days()->whereDate('date', now()->toDateString())->first() ?? $trip->days()->first());
    }
}
