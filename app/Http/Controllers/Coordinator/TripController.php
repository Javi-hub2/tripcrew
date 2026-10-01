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

        // Voortgangsdashboard: per reis checklistvoortgang en bezetting van de activiteiten.
        $trips = Trip::withCount(['travelers', 'pendingRegistrations'])
            ->with(['activities' => fn ($q) => $q->withCount('choices')])
            ->orderBy('start_date')
            ->get()
            ->each(function (Trip $trip) {
                $trip->checklists_done = collect($trip->checklistPercentages())->filter(fn ($pct) => $pct === 100)->count();
                $trip->seats_taken = $trip->activities->sum('choices_count');
                $trip->seats_total = $trip->activities->sum('capacity');
                $trip->activities_full = $trip->activities->filter(fn ($a) => $a->choices_count >= $a->capacity)->count();
            });

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
        // Bestaande dagen in PHP vergelijken, niet via firstOrCreate(['date' => 'Y-m-d']):
        // de date-cast slaat op SQLite 'Y-m-d 00:00:00' op, waardoor die query een bestaande
        // dag niet vond en de unieke index (trip_id, date) de dubbele dag weigerde (500).
        $existing = $trip->days()->get()->map(fn (TripDay $day) => $day->date->toDateString());

        foreach (Carbon::parse($start)->toPeriod(Carbon::parse($end)) as $date) {
            if (! $existing->contains($date->toDateString())) {
                $trip->days()->create(['date' => $date->toDateString()]);
            }
        }
    }
}
