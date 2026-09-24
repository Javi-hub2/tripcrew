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

        // Heeft de reis vaste punten, dan telt alleen die voltooiing; anders de eigen punten.
        $requiredCount = $trip->requiredChecklistItems()->count();

        $participants = $trip->travelers()
            ->with([
                'activityChoices' => fn ($q) => $q->whereHas('activity.tripDay', fn ($d) => $d->where('trip_id', $trip->id)),
                'activityChoices.activity',
                'checklistItems' => fn ($q) => $q->where('trip_id', $trip->id),
                'completedTripChecklistItems' => fn ($q) => $q->where('trip_id', $trip->id)])
            ->get()
            ->map(function ($traveler) use ($requiredCount) {
                if ($requiredCount) {
                    $done = $traveler->completedTripChecklistItems->count();
                    $total = $requiredCount;
                } else {
                    $done = $traveler->checklistItems->where('checked', true)->count();
                    $total = $traveler->checklistItems->count();
                }
                $percentage = $total ? (int) round($done / $total * 100) : 0;

                return (object) [
                    'user' => $traveler,
                    'choices' => $traveler->activityChoices->pluck('activity'),
                    'checklist_percentage' => $percentage,
                ];
            });

        return view('coordinator.participants.index', compact('trip', 'participants'));
    }
}
