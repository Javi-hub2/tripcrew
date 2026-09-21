<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityChoiceTest extends TestCase
{
    use RefreshDatabase;

    private function travelerOnTrip(Trip $trip): User
    {
        $user = User::factory()->create();
        $trip->travelers()->attach($user);

        return $user;
    }

    private function activity(array $attributes = []): Activity
    {
        $day = TripDay::factory()->create();

        return Activity::factory()->create(['trip_day_id' => $day->id] + $attributes);
    }

    /** FE-04 happy path */
    public function test_traveler_can_choose_activity(): void
    {
        $activity = $this->activity(['capacity' => 3]);
        $user = $this->travelerOnTrip($activity->tripDay->trip);

        $this->actingAs($user)
            ->post(route('activities.choose', $activity))
            ->assertSessionHas('success', 'Keuze gelukt.');

        $this->assertDatabaseHas('activity_choices', ['user_id' => $user->id, 'activity_id' => $activity->id]);
        $this->assertSame(2, $activity->seatsLeft());
    }

    /** FE-04 foutgeval: activiteit vol */
    public function test_cannot_choose_full_activity(): void
    {
        $activity = $this->activity(['capacity' => 1]);
        $trip = $activity->tripDay->trip;
        ActivityChoice::create(['user_id' => $this->travelerOnTrip($trip)->id, 'activity_id' => $activity->id]);

        $late = $this->travelerOnTrip($trip);

        $this->actingAs($late)
            ->post(route('activities.choose', $activity))
            ->assertSessionHas('error', 'Deze activiteit zit vol.');

        $this->assertSame(1, $activity->choices()->count()); // capaciteit nooit overschreden
    }

    /** FE-04 foutgeval: deadline voorbij */
    public function test_cannot_choose_after_deadline(): void
    {
        $activity = $this->activity(['deadline' => now()->subHour()]);
        $user = $this->travelerOnTrip($activity->tripDay->trip);

        $this->actingAs($user)
            ->post(route('activities.choose', $activity))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('activity_choices', 0);
    }

    /** TE-03: reiziger van een andere reis mag niet kiezen */
    public function test_traveler_of_other_trip_gets_403(): void
    {
        $activity = $this->activity();
        $outsider = $this->travelerOnTrip(Trip::factory()->create());

        $this->actingAs($outsider)
            ->post(route('activities.choose', $activity))
            ->assertForbidden();
    }

    /** FE-05 + TE-03: andermans keuze annuleren -> 403 */
    public function test_cannot_cancel_someone_elses_choice(): void
    {
        $activity = $this->activity();
        $trip = $activity->tripDay->trip;
        $choice = ActivityChoice::create(['user_id' => $this->travelerOnTrip($trip)->id, 'activity_id' => $activity->id]);

        $this->actingAs($this->travelerOnTrip($trip))
            ->delete(route('choices.destroy', $choice))
            ->assertForbidden();

        $this->assertModelExists($choice);
    }

    /** FE-05: annuleren geeft plek vrij */
    public function test_cancel_frees_the_seat(): void
    {
        $activity = $this->activity(['capacity' => 1]);
        $user = $this->travelerOnTrip($activity->tripDay->trip);
        $choice = ActivityChoice::create(['user_id' => $user->id, 'activity_id' => $activity->id]);

        $this->actingAs($user)->delete(route('choices.destroy', $choice))->assertSessionHas('success');

        $this->assertSame(1, $activity->seatsLeft());
    }

    /** FE-05 foutgeval: annuleren na deadline kan niet */
    public function test_cannot_cancel_after_deadline(): void
    {
        $activity = $this->activity(['deadline' => now()->subHour()]);
        $user = $this->travelerOnTrip($activity->tripDay->trip);
        $choice = ActivityChoice::create(['user_id' => $user->id, 'activity_id' => $activity->id]);

        $this->actingAs($user)->delete(route('choices.destroy', $choice))->assertForbidden();
        $this->assertModelExists($choice);
    }
}
