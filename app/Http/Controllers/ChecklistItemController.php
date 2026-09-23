<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistItemRequest;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// FE-05: checklist-item toevoegen en aan-/uitvinken.
class ChecklistItemController extends Controller
{
    public function store(StoreChecklistItemRequest $request, Trip $trip): RedirectResponse
    {
        $this->authorize('view', $trip);

        Auth::user()->checklistItems()->create([
            'trip_id' => $trip->id,
            'label' => $request->validated()['label'],
        ]);

        return back()->with('success', 'Checklist-item toegevoegd.');
    }

    public function toggle(ChecklistItem $checklistItem): RedirectResponse
    {
        $this->authorize('update', $checklistItem);

        $checklistItem->update(['checked' => ! $checklistItem->checked]);

        return back()->with('success', 'Checklist bijgewerkt.');
    }

    /**
     * Vast checklistpunt van de coördinator aan- of uitvinken, alleen voor de ingelogde
     * reiziger zelf. Alleen wie voor de reis is goedgekeurd mag dat (TripPolicy::view).
     */
    public function toggleRequired(Trip $trip, TripChecklistItem $tripChecklistItem): RedirectResponse
    {
        abort_unless($tripChecklistItem->trip_id === $trip->id, 404);
        $this->authorize('view', $trip);

        $tripChecklistItem->completedBy()->toggle(Auth::id());

        return back()->with('success', 'Checklist bijgewerkt.');
    }
}
