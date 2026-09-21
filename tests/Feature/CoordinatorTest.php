<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    /** FE-06 happy path: reis + dagen aangemaakt */
    public function test_coordinator_can_create_trip_with_days(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->post(route('coordinator.trips.store'), [
                'name' => 'Testreis',
                'start_date' => '2030-06-01',
                'end_date' => '2030-06-03',
            ])->assertSessionHas('success');

        $this->assertSame(3, Trip::firstWhere('name', 'Testreis')->days()->count());
    }

    /** FE-06 foutgeval: einddatum vóór begindatum */
    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->post(route('coordinator.trips.store'), [
                'name' => 'Foute reis',
                'start_date' => '2030-06-05',
                'end_date' => '2030-06-01',
            ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('trips', 0);
    }

    /** FE-06 foutgeval: reis met deelnemers niet verwijderen */
    public function test_trip_with_participants_cannot_be_deleted(): void
    {
        $trip = Trip::factory()->create();
        $trip->travelers()->attach(User::factory()->create());

        $this->actingAs(User::factory()->coordinator()->create())
            ->delete(route('coordinator.trips.destroy', $trip))
            ->assertSessionHas('error');

        $this->assertModelExists($trip);
    }

    /** FE-07 foutgeval: capaciteit 0 / negatief / geen getal */
    public function test_invalid_capacity_is_rejected(): void
    {
        $coordinator = User::factory()->coordinator()->create();
        $trip = Trip::factory()->create();
        $day = $trip->days()->create(['date' => '2030-06-01']);

        foreach ([0, -3, 'abc'] as $capacity) {
            $this->actingAs($coordinator)
                ->post(route('coordinator.trips.activities.store', $trip), [
                    'trip_day_id' => $day->id,
                    'name' => 'Kajakken',
                    'capacity' => $capacity,
                    'deadline' => '2030-05-30 12:00',
                ])->assertSessionHasErrors('capacity');
        }

        $this->assertDatabaseCount('activities', 0);
    }

    /** TE-03: reiziger kan coördinatorschermen niet openen */
    public function test_traveler_cannot_access_coordinator_routes(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->travelers()->attach($traveler);

        $this->actingAs($traveler)->get(route('coordinator.trips.index'))->assertForbidden();
        $this->actingAs($traveler)->get(route('coordinator.trips.participants.index', $trip))->assertForbidden();
    }

    /** FE-08: percentage afgeronde checklist-items */
    public function test_participants_overview_shows_checklist_percentage(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create(['name' => 'Sam Test']);
        $trip->travelers()->attach($traveler);
        ChecklistItem::factory()->create(['user_id' => $traveler->id, 'trip_id' => $trip->id, 'checked' => true]);
        ChecklistItem::factory()->create(['user_id' => $traveler->id, 'trip_id' => $trip->id, 'checked' => false]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.participants.index', $trip))
            ->assertOk()
            ->assertSee('Sam Test')
            ->assertSee('50%');
    }

    /** FE-08 foutgeval: nog geen deelnemers */
    public function test_empty_participants_message(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.participants.index', $trip))
            ->assertSee('Nog geen deelnemers voor deze reis.');
    }
}
