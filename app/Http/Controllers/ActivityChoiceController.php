<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// FE-04 & FE-05 (annuleren)
class ActivityChoiceController extends Controller
{
    public function myChoices(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $choices = Auth::user()->activityChoices()
            ->whereHas('activity.tripDay', fn ($q) => $q->where('trip_id', $trip->id))
            ->with('activity.tripDay')
            ->get();

        $checklistItems = Auth::user()->checklistItems()->where('trip_id', $trip->id)->get();

        return view('traveler.my-choices', compact('trip', 'choices', 'checklistItems'));
    }

    /**
     * FE-04, TE-05: kiezen gebeurt binnen een transactie met een row-lock op de
     * activiteit, zodat gelijktijdige aanvragen nooit meer plekken uitgeven
     * dan de capaciteit toelaat.
     */
    public function store(Activity $activity): RedirectResponse
    {
        $this->authorize('choose', $activity);

        if ($activity->deadlineHasPassed()) {
            return back()->with('error', 'De deadline voor deze activiteit is voorbij.');
        }

        try {
            DB::transaction(function () use ($activity) {
                // Lock de activiteitenrij zodat gelijktijdige requests op
                // dezelfde rij wachten tot deze transactie klaar is.
                $locked = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();

                if ($locked->deadlineHasPassed()) {
                    throw new \App\Exceptions\ActivityChoiceRejected('De deadline voor deze activiteit is voorbij.');
                }

                $takenSeats = ActivityChoice::where('activity_id', $locked->id)->count();

                if ($takenSeats >= $locked->capacity) {
                    throw new \App\Exceptions\ActivityChoiceRejected('Deze activiteit zit vol.');
                }

                ActivityChoice::firstOrCreate([
                    'user_id' => Auth::id(),
                    'activity_id' => $locked->id,
                ]);
            });
        } catch (\App\Exceptions\ActivityChoiceRejected $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Keuze gelukt.');
    }

    public function destroy(ActivityChoice $choice): RedirectResponse
    {
        $this->authorize('delete', $choice);

        $choice->delete();

        return back()->with('success', 'Je keuze is geannuleerd.');
    }
}
