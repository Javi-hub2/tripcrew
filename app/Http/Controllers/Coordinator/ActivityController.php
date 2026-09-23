<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

// FE-07
class ActivityController extends Controller
{
    public function index(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $activities = Activity::whereHas('tripDay', fn ($q) => $q->where('trip_id', $trip->id))
            ->withCount('choices')
            ->with('tripDay')
            ->get();

        return view('coordinator.activities.index', compact('trip', 'activities'));
    }

    public function create(Trip $trip): View
    {
        $this->authorize('create', Activity::class);

        return view('coordinator.activities.create', [
            'trip' => $trip,
            'days' => $trip->days,
        ]);
    }

    public function store(StoreActivityRequest $request, Trip $trip): RedirectResponse
    {
        Activity::create($request->validated());

        return redirect()->route('coordinator.trips.activities.index', $trip)
            ->with('success', 'Activiteit toegevoegd.');
    }

    public function edit(Trip $trip, Activity $activity): View
    {
        $this->ensureBelongsTo($trip, $activity);
        $this->authorize('update', $activity);

        return view('coordinator.activities.edit', [
            'trip' => $trip,
            'activity' => $activity,
            'days' => $trip->days,
        ]);
    }

    public function update(StoreActivityRequest $request, Trip $trip, Activity $activity): RedirectResponse
    {
        $this->ensureBelongsTo($trip, $activity);
        $this->authorize('update', $activity);

        $activity->update($request->validated());

        return redirect()->route('coordinator.trips.activities.index', $trip)
            ->with('success', 'Activiteit bijgewerkt.');
    }

    public function destroy(Trip $trip, Activity $activity): RedirectResponse
    {
        $this->ensureBelongsTo($trip, $activity);
        $this->authorize('delete', $activity);

        $activity->delete();

        return redirect()->route('coordinator.trips.activities.index', $trip)
            ->with('success', 'Activiteit verwijderd.');
    }

    /** Activiteit uit een andere reis via de URL aanspreken -> 404. */
    private function ensureBelongsTo(Trip $trip, Activity $activity): void
    {
        abort_unless($activity->tripDay->trip_id === $trip->id, 404);
    }
}
