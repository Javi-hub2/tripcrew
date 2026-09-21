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
}
