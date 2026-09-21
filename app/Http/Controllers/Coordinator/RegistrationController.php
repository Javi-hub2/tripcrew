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
        $registration = $trip->registrations()->where('user_id', $user->id)->first();

        abort_unless($registration !== null, 404);

        if ($registration->pivot->status !== RegistrationStatus::Pending->value) {
            return redirect()->route('coordinator.registrations.index')
                ->with('error', 'Deze aanvraag is al afgehandeld.');
        }

        $trip->registrations()->updateExistingPivot($user->id, [
            'status' => $status->value,
            'decided_at' => now(),
            'decided_by' => Auth::id(),
        ]);

        return redirect()->route('coordinator.registrations.index')->with('success', $message);
    }
}
