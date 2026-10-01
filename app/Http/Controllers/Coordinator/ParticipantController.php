<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Contracts\View\View;

// FE-08
class ParticipantController extends Controller
{
    public function index(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $percentages = $trip->checklistPercentages();

        $participants = $trip->travelers()
            ->with([
                'activityChoices' => fn ($q) => $q->whereHas('activity.tripDay', fn ($d) => $d->where('trip_id', $trip->id)),
                'activityChoices.activity',
            ])
            ->get()
            ->map(fn ($traveler) => (object) [
                'user' => $traveler,
                'choices' => $traveler->activityChoices->pluck('activity'),
                'checklist_percentage' => $percentages[$traveler->id] ?? 0,
            ]);

        return view('coordinator.participants.index', compact('trip', 'participants'));
    }
}
