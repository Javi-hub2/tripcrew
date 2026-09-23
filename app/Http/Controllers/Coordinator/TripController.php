<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripRequest;
use App\Models\Trip;
use App\Models\TripDay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// FE-06
class TripController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Trip::class);

        $trips = Trip::withCount('travelers')->orderBy('start_date')->get();

        // Aantal openstaande aanvragen over alle reizen heen (eindreview: de
        // coördinator had vanaf dit scherm geen weg naar het aanvragenscherm).
        $pendingRegistrationsCount = DB::table('trip_user')
            ->where('status', RegistrationStatus::Pending->value)
            ->count();

        return view('coordinator.trips.index', compact('trips', 'pendingRegistrationsCount'));
    }

    public function create(): View
    {
        $this->authorize('create', Trip::class);

        return view('coordinator.trips.create');
    }

    public function store(StoreTripRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $trip = Trip::create($data);

        $this->generateDays($trip, $data['start_date'], $data['end_date']);

        return redirect()->route('coordinator.trips.index')->with('success', 'Reis aangemaakt.');
    }

    public function edit(Trip $trip): View
    {
        $this->authorize('update', $trip);

        return view('coordinator.trips.edit', compact('trip'));
    }

    public function update(StoreTripRequest $request, Trip $trip): RedirectResponse
    {
        $data = $request->validated();
        $trip->update($data);

        // Dagen opnieuw opbouwen als de periode is gewijzigd (eenvoudige aanpak
        // voor de MVP: bestaande dagen buiten de nieuwe periode blijven staan
        // als er al programma/activiteiten aan hangen, nieuwe dagen worden
        // aangemaakt voor de uitgebreide periode).
        $this->generateDays($trip, $data['start_date'], $data['end_date']);

        return redirect()->route('coordinator.trips.index')->with('success', 'Reis bijgewerkt.');
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        $this->authorize('delete', $trip);

        if ($trip->hasParticipants()) {
            return back()->with('error', 'Deze reis heeft nog deelnemers en kan niet verwijderd worden.');
        }

        $trip->delete();

        return redirect()->route('coordinator.trips.index')->with('success', 'Reis verwijderd.');
    }

    private function generateDays(Trip $trip, string $start, string $end): void
    {
        $period = Carbon::parse($start)->toPeriod(Carbon::parse($end));

        foreach ($period as $date) {
            TripDay::firstOrCreate([
                'trip_id' => $trip->id,
                'date' => $date->toDateString(),
            ]);
        }
    }
}
