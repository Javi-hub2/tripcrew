<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TripRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_registration_does_not_grant_access_to_the_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $trip))
            ->assertForbidden();
    }

    public function test_approved_registration_grants_access_to_the_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $trip))
            ->assertOk();
    }

    /**
     * Eindreview-bevinding: er was geen enkele link naar het inschrijfscherm zodra
     * een reiziger eenmaal is goedgekeurd, waardoor hij zich nooit voor een tweede
     * reis kon inschrijven of tussen goedgekeurde reizen kon wisselen.
     */
    public function test_approved_traveler_sees_a_link_back_to_my_trips_on_the_day_program(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $trip))
            ->assertOk()
            ->assertSee('Mijn reizen')
            ->assertSee(route('traveler.registrations.index'), false);
    }

    public function test_only_approved_travelers_count_as_participants(): void
    {
        $trip = Trip::factory()->create();
        $trip->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Approved->value]);
        $trip->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Pending->value]);

        $this->assertSame(1, $trip->travelers()->count());
        $this->assertSame(1, $trip->pendingRegistrations()->count());
    }

    public function test_pending_traveler_cannot_choose_an_activity(): void
    {
        $activity = \App\Models\Activity::factory()->create();
        $trip = $activity->tripDay->trip;
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->post(route('activities.choose', $activity))
            ->assertForbidden();
    }

    public function test_traveler_can_register_for_a_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();

        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertRedirect(route('traveler.registrations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Pending->value,
        ]);
    }

    public function test_traveler_can_register_for_multiple_trips(): void
    {
        $traveler = User::factory()->create();
        $first = Trip::factory()->create();
        $second = Trip::factory()->create();

        $this->actingAs($traveler)->post(route('traveler.registrations.store', $first));
        $this->actingAs($traveler)->post(route('traveler.registrations.store', $second));

        $this->assertSame(2, $traveler->trips()->count());
    }

    public function test_registering_twice_does_not_create_a_second_row(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();

        $this->actingAs($traveler)->post(route('traveler.registrations.store', $trip));
        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertSessionHas('error');

        $this->assertSame(1, $traveler->trips()->count());
    }

    public function test_rejected_registration_can_be_submitted_again(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Rejected->value]);

        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Pending->value,
        ]);
    }

    public function test_coordinator_cannot_use_the_traveler_registration_screen(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('traveler.registrations.index'))
            ->assertForbidden();
    }

    public function test_approved_registration_is_not_reset_by_registering_again(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Approved->value,
        ]);
    }

    public function test_coordinator_sees_pending_registrations(): void
    {
        $trip = Trip::factory()->create(['name' => 'Skireis Oostenrijk']);
        $traveler = User::factory()->create(['name' => 'Sam Test']);
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.registrations.index'))
            ->assertOk()
            ->assertSee('Sam Test')
            ->assertSee('Skireis Oostenrijk');
    }

    /**
     * Eindreview-bevinding: het ontwerp vraagt op het aanvragenscherm om "naam,
     * e-mailadres, reis en datum". De aanvraagdatum werd al opgeslagen (requested_at)
     * maar nergens getoond.
     */
    public function test_coordinator_sees_the_request_date_in_a_dutch_format(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create(['name' => 'Sam Test']);
        $trip->registrations()->attach($traveler, [
            'status' => RegistrationStatus::Pending->value,
            'requested_at' => '2030-01-15 10:00:00',
        ]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.registrations.index'))
            ->assertOk()
            ->assertSee('15-01-2030');
    }

    public function test_coordinator_can_approve_a_registration(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);
        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertRedirect(route('coordinator.registrations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Approved->value,
            'decided_by' => $coordinator->id,
        ]);

        $this->actingAs($traveler)->get(route('traveler.dashboard', $trip))->assertOk();
    }

    public function test_coordinator_can_reject_a_registration(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->patch(route('coordinator.registrations.reject', [$trip, $traveler]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Rejected->value,
        ]);
    }

    public function test_traveler_cannot_approve_registrations(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertForbidden();
    }

    public function test_approving_a_nonexistent_registration_gives_no_success_message(): void
    {
        $trip = Trip::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->patch(route('coordinator.registrations.approve', [$trip, $stranger]))
            ->assertNotFound();

        $this->assertDatabaseMissing('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $stranger->id,
        ]);
    }

    public function test_already_decided_registration_cannot_be_decided_again(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->patch(route('coordinator.registrations.reject', [$trip, $traveler]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Approved->value,
        ]);
    }

    /**
     * Eindreview-bevinding: de statuscontrole en de schrijfactie in decide() waren
     * niet atomair, waardoor twee coördinatoren dezelfde pending aanvraag allebei
     * konden "winnen". De update moet nu voorwaardelijk zijn (where status=pending)
     * en beslissen op het aantal geraakte rijen, zodat alleen de eerste beslissing
     * ooit wegschrijft — ook al lopen twee verzoeken tegelijk binnen. Dat gedrag
     * simuleren we hier sequentieel: de tweede beslissing moet de "al afgehandeld"-
     * melding krijgen en decided_by/status van de eerste moeten intact blijven.
     */
    /**
     * Eindreview-punt 6: de beslissing moet in één voorwaardelijke UPDATE gebeuren.
     * Deze test bootst de race na: zodra de controller de aanvraag opzoekt, beslist
     * een tweede coördinator ertussendoor. Met een lees-dan-schrijf-implementatie
     * overschrijft de eerste coördinator die beslissing alsnog.
     */
    public function test_a_decision_made_between_lookup_and_write_is_not_overwritten(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);
        $first = User::factory()->coordinator()->create();
        $second = User::factory()->coordinator()->create();

        $raceGereden = false;
        DB::listen(function ($query) use ($trip, $traveler, $second, &$raceGereden) {
            if ($raceGereden || ! str_contains($query->sql, 'trip_user') || ! str_starts_with(strtolower($query->sql), 'select')) {
                return;
            }

            // De tweede coördinator is net iets eerder klaar.
            $raceGereden = true;
            DB::table('trip_user')
                ->where('trip_id', $trip->id)
                ->where('user_id', $traveler->id)
                ->update([
                    'status' => RegistrationStatus::Rejected->value,
                    'decided_at' => now(),
                    'decided_by' => $second->id,
                ]);
        });

        $this->actingAs($first)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertSessionHas('error', 'Deze aanvraag is al afgehandeld.');

        $this->assertTrue($raceGereden, 'De race is niet nagebootst; de test zegt dan niets.');
        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Rejected->value,
            'decided_by' => $second->id,
        ]);
    }

    public function test_two_coordinators_deciding_on_the_same_pending_registration_only_one_wins(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);
        $first = User::factory()->coordinator()->create();
        $second = User::factory()->coordinator()->create();

        $this->actingAs($first)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertSessionHas('success');

        $this->actingAs($second)
            ->patch(route('coordinator.registrations.reject', [$trip, $traveler]))
            ->assertSessionHas('error', 'Deze aanvraag is al afgehandeld.');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Approved->value,
            'decided_by' => $first->id,
        ]);
    }
}
