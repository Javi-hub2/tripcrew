<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// FE-06: de coördinator beslist wie er meegaat.
class RegistrationController extends Controller
{
    public function index(): View
    {
        $trips = Trip::with('pendingRegistrations')
            ->orderBy('start_date')
            ->get()
            ->filter(fn (Trip $trip) => $trip->pendingRegistrations->isNotEmpty());

        return view('coordinator.registrations.index', ['trips' => $trips]);
    }

    public function approve(Trip $trip, User $user): RedirectResponse
    {
        return $this->decide($trip, $user, RegistrationStatus::Approved, "{$user->name} doet mee aan {$trip->name}.");
    }

    public function reject(Trip $trip, User $user): RedirectResponse
    {
        return $this->decide($trip, $user, RegistrationStatus::Rejected, "De aanvraag van {$user->name} is afgewezen.");
    }

    private function decide(Trip $trip, User $user, RegistrationStatus $status, string $message): RedirectResponse
    {
        abort_unless($trip->registrations()->where('user_id', $user->id)->exists(), 404);

        // Eén voorwaardelijke UPDATE: alleen bijwerken zolang de aanvraag nog open
        // staat. Lezen-en-dan-schrijven liet twee coördinatoren die tegelijk beslissen
        // allebei door de controle glippen, waarna de laatste schrijver won en beiden
        // "gelukt" te zien kregen. Het aantal geraakte rijen is nu de beslissing.
        $bijgewerkt = DB::table('trip_user')
            ->where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->where('status', RegistrationStatus::Pending->value)
            ->update([
                'status' => $status->value,
                'decided_at' => now(),
                'decided_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        if ($bijgewerkt === 0) {
            return redirect()->route('coordinator.registrations.index')
                ->with('error', 'Deze aanvraag is al afgehandeld.');
        }

        return redirect()->route('coordinator.registrations.index')->with('success', $message);
    }
}
