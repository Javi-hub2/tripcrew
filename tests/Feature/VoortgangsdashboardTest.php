<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripChecklistItem;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bijlage briefing: "een voortgangsdashboard voor coördinatoren". Het reizenoverzicht toont
 * per reis hoeveel deelnemers hun checklist af hebben en hoe vol de activiteiten zitten.
 */
class VoortgangsdashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = User::factory()->coordinator()->create();
        $this->trip = Trip::factory()->create();
    }

    private function traveler(RegistrationStatus $status = RegistrationStatus::Approved, ?Trip $trip = null): User
    {
        $traveler = User::factory()->create();
        ($trip ?? $this->trip)->registrations()->attach($traveler, ['status' => $status->value]);

        return $traveler;
    }

    private function activity(int $capacity, int $taken, ?Trip $trip = null): Activity
    {
        $day = TripDay::factory()->create(['trip_id' => ($trip ?? $this->trip)->id]);
        $activity = Activity::factory()->create(['trip_day_id' => $day->id, 'capacity' => $capacity]);

        for ($i = 0; $i < $taken; $i++) {
            ActivityChoice::create(['user_id' => $this->traveler(trip: $trip)->id, 'activity_id' => $activity->id]);
        }

        return $activity;
    }

    /** @return TripChecklistItem[] */
    private function requiredItems(int $count): array
    {
        return collect(range(1, $count))
            ->map(fn ($i) => TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => "Punt $i"]))
            ->all();
    }

    private function dashboard()
    {
        return $this->actingAs($this->coordinator)->get(route('coordinator.trips.index'))->assertOk();
    }

    public function test_counts_travelers_who_completed_all_required_items(): void
    {
        [$a, $b] = $this->requiredItems(2);
        $klaar = $this->traveler();
        $half = $this->traveler();
        $this->traveler();

        $a->completedBy()->attach([$klaar->id, $half->id]);
        $b->completedBy()->attach($klaar);

        $this->dashboard()->assertSee('1 van 3 deelnemers klaar');
    }

    public function test_without_required_items_own_checklist_counts(): void
    {
        $klaar = $this->traveler();
        $this->traveler();
        ChecklistItem::factory()->create(['user_id' => $klaar->id, 'trip_id' => $this->trip->id, 'checked' => true]);

        $this->dashboard()->assertSee('1 van 2 deelnemers klaar');
    }

    public function test_shows_seats_taken_and_full_activities(): void
    {
        $this->activity(capacity: 2, taken: 2);
        $this->activity(capacity: 5, taken: 1);

        $this->dashboard()
            ->assertSee('3 van 7 plekken bezet')
            ->assertSee('1 activiteit vol');
    }

    public function test_figures_of_another_trip_are_not_mixed_in(): void
    {
        $andere = Trip::factory()->create();
        $this->activity(capacity: 4, taken: 1);
        $this->activity(capacity: 10, taken: 3, trip: $andere);

        $this->dashboard()
            ->assertSee('1 van 4 plekken bezet')
            ->assertSee('3 van 10 plekken bezet')
            ->assertDontSee('4 van 14 plekken bezet');
    }

    public function test_pending_requests_are_shown_per_trip(): void
    {
        $this->traveler(RegistrationStatus::Pending);

        $this->dashboard()->assertSee('1 aanvraag open');
    }

    public function test_empty_trip_shows_clear_messages(): void
    {
        $this->dashboard()
            ->assertSee('Nog geen deelnemers')
            ->assertSee('Nog geen activiteiten')
            ->assertDontSee('aanvraag open');
    }

    public function test_participant_overview_still_shows_the_same_percentage(): void
    {
        [$a, $b] = $this->requiredItems(2);
        $half = $this->traveler();
        $a->completedBy()->attach($half);

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.participants.index', $this->trip))
            ->assertSee('50%');
    }
}
