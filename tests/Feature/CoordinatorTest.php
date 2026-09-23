<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\ActivityChoice;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Eindreview-bevinding: vanaf het reizenoverzicht was er geen weg naar het
     * aanvragenscherm als er nog geen enkele reis was. Het ontwerp vraagt om
     * "Openstaande aanvragen (n)" met het aantal over alle reizen heen.
     */
    public function test_trips_index_shows_the_total_pending_registrations_count_and_a_link(): void
    {
        $tripA = Trip::factory()->create();
        $tripB = Trip::factory()->create();
        $tripA->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Pending->value]);
        $tripA->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Pending->value]);
        $tripB->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.index'))
            ->assertOk()
            ->assertSee('Openstaande aanvragen (2)')
            ->assertSee(route('coordinator.registrations.index'), false);
    }

    /** Zonder openstaande aanvragen toont de teller 0, maar de link blijft aanwezig. */
    public function test_trips_index_shows_zero_pending_registrations_when_there_are_none(): void
    {
        Trip::factory()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.index'))
            ->assertOk()
            ->assertSee('Openstaande aanvragen (0)')
            ->assertSee(route('coordinator.registrations.index'), false);
    }

    /**
     * Eindreview-bevinding: capaciteit mag bij het bewerken van een bestaande
     * activiteit niet lager gezet worden dan het aantal reeds gemaakte keuzes.
     */
    public function test_capacity_cannot_be_lowered_below_the_number_of_existing_choices(): void
    {
        $trip = Trip::factory()->create();
        $day = $trip->days()->create(['date' => '2030-06-01']);
        $activity = Activity::factory()->create(['trip_day_id' => $day->id, 'capacity' => 5]);
        foreach (range(1, 3) as $i) {
            ActivityChoice::create(['user_id' => User::factory()->create()->id, 'activity_id' => $activity->id]);
        }

        $this->actingAs(User::factory()->coordinator()->create())
            ->put(route('coordinator.trips.activities.update', [$trip, $activity]), [
                'trip_day_id' => $day->id,
                'name' => $activity->name,
                'capacity' => 2,
                'deadline' => $activity->deadline->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('capacity');

        $this->assertSame(5, $activity->fresh()->capacity);
    }

    /** Capaciteit gelijk aan het aantal keuzes mag wel (grensgeval). */
    public function test_capacity_may_be_lowered_to_exactly_the_number_of_existing_choices(): void
    {
        $trip = Trip::factory()->create();
        $day = $trip->days()->create(['date' => '2030-06-01']);
        $activity = Activity::factory()->create(['trip_day_id' => $day->id, 'capacity' => 5]);
        foreach (range(1, 3) as $i) {
            ActivityChoice::create(['user_id' => User::factory()->create()->id, 'activity_id' => $activity->id]);
        }

        $this->actingAs(User::factory()->coordinator()->create())
            ->put(route('coordinator.trips.activities.update', [$trip, $activity]), [
                'trip_day_id' => $day->id,
                'name' => $activity->name,
                'capacity' => 3,
                'deadline' => $activity->deadline->format('Y-m-d H:i'),
            ])
            ->assertSessionHas('success');

        $this->assertSame(3, $activity->fresh()->capacity);
    }

    /**
     * Eindreview-bevinding: capaciteit 0 op een bestaande activiteit (bv. data van
     * vóór de validatie) mag het coördinatorscherm niet laten crashen op deling door
     * nul.
     */
    public function test_activities_index_does_not_crash_when_capacity_is_zero(): void
    {
        $trip = Trip::factory()->create();
        $day = $trip->days()->create(['date' => '2030-06-01']);
        Activity::factory()->create(['trip_day_id' => $day->id, 'capacity' => 0]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.activities.index', $trip))
            ->assertOk();
    }

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
        $trip->registrations()->attach(User::factory()->create(), ['status' => \App\Enums\RegistrationStatus::Approved->value]);

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
        $trip->registrations()->attach($traveler, ['status' => \App\Enums\RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)->get(route('coordinator.trips.index'))->assertForbidden();
        $this->actingAs($traveler)->get(route('coordinator.trips.participants.index', $trip))->assertForbidden();
    }

    /** FE-08: percentage afgeronde checklist-items */
    public function test_participants_overview_shows_checklist_percentage(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create(['name' => 'Sam Test']);
        $trip->registrations()->attach($traveler, ['status' => \App\Enums\RegistrationStatus::Approved->value]);
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

    /**
     * Eindreview-restpunt: update() riep als enige actie de ActivityPolicy niet aan.
     * Rol-middleware en StoreActivityRequest laten nu toevallig dezelfde mensen door,
     * dus met een reiziger valt dat niet te zien. Daarom een policy die alles weigert:
     * dan moet ook update() weigeren.
     */
    public function test_updating_an_activity_goes_through_the_activity_policy(): void
    {
        Gate::policy(Activity::class, DenyEverythingPolicy::class);

        $trip = Trip::factory()->create();
        $day = $trip->days()->create(['date' => '2030-06-01']);
        $activity = Activity::factory()->create(['trip_day_id' => $day->id, 'name' => 'Kajakken']);

        $this->actingAs(User::factory()->coordinator()->create())
            ->put(route('coordinator.trips.activities.update', [$trip, $activity]), [
                'trip_day_id' => $day->id,
                'name' => 'Gewijzigd',
                'capacity' => 5,
                'deadline' => $activity->deadline->format('Y-m-d H:i'),
            ])
            ->assertForbidden();

        $this->assertSame('Kajakken', $activity->fresh()->name);
    }
}

class DenyEverythingPolicy
{
    public function __call(string $method, array $arguments): bool
    {
        return false;
    }
}
