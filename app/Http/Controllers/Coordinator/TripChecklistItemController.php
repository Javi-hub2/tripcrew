<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripChecklistItemRequest;
use App\Models\Trip;
use App\Models\TripChecklistItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

// Vaste checklistpunten per reis: de coördinator bepaalt wat elke reiziger moet afvinken.
class TripChecklistItemController extends Controller
{
    public function index(Trip $trip): View
    {
        $this->authorize('update', $trip);

        $items = $trip->requiredChecklistItems()->withCount('completedBy')->get();
        $travelerCount = $trip->travelers()->count();

        return view('coordinator.checklist.index', compact('trip', 'items', 'travelerCount'));
    }

    public function store(StoreTripChecklistItemRequest $request, Trip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);

        $trip->requiredChecklistItems()->create(['label' => $request->validated()['label']]);

        return back()->with('success', 'Checklistpunt toegevoegd.');
    }

    public function destroy(Trip $trip, TripChecklistItem $tripChecklistItem): RedirectResponse
    {
        abort_unless($tripChecklistItem->trip_id === $trip->id, 404);
        $this->authorize('update', $trip);

        $tripChecklistItem->delete();

        return back()->with('success', 'Checklistpunt verwijderd.');
    }
}
