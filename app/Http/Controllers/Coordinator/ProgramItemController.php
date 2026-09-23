<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramItemRequest;
use App\Http\Requests\UpdateProgramItemRequest;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

// Briefing: de coördinator beheert de programmaonderdelen per reisdag.
class ProgramItemController extends Controller
{
    public function index(Trip $trip): View
    {
        $this->authorize('update', $trip);

        $days = $trip->days()->with('programItems')->get();

        return view('coordinator.program.index', compact('trip', 'days'));
    }

    public function store(StoreProgramItemRequest $request, Trip $trip, TripDay $tripDay): RedirectResponse
    {
        abort_unless($tripDay->trip_id === $trip->id, 404);
        $this->authorize('update', $trip);

        $tripDay->programItems()->create($request->validated());

        return back()->with('success', 'Programmaonderdeel toegevoegd.');
    }

    public function edit(Trip $trip, ProgramItem $programItem): View
    {
        $this->ensureBelongsTo($trip, $programItem);
        $this->authorize('update', $trip);

        return view('coordinator.program.edit', [
            'trip' => $trip,
            'item' => $programItem,
            'days' => $trip->days,
        ]);
    }

    public function update(UpdateProgramItemRequest $request, Trip $trip, ProgramItem $programItem): RedirectResponse
    {
        $this->ensureBelongsTo($trip, $programItem);
        $this->authorize('update', $trip);

        $programItem->update($request->validated());

        return redirect()->route('coordinator.trips.program.index', $trip)
            ->with('success', 'Programmaonderdeel bijgewerkt.');
    }

    public function destroy(Trip $trip, ProgramItem $programItem): RedirectResponse
    {
        $this->ensureBelongsTo($trip, $programItem);
        $this->authorize('update', $trip);

        $programItem->delete();

        return back()->with('success', 'Programmaonderdeel verwijderd.');
    }

    /** Onderdeel uit een andere reis via de URL aanspreken -> 404. */
    private function ensureBelongsTo(Trip $trip, ProgramItem $programItem): void
    {
        abort_unless($programItem->tripDay->trip_id === $trip->id, 404);
    }
}
