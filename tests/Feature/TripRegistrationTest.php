<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
