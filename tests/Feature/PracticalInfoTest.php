<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Briefing: reiziger bekijkt "dagprogramma en praktische informatie"; de coördinator vult die in. */
class PracticalInfoTest extends TestCase
{
    use RefreshDatabase;

    private function approvedTraveler(Trip $trip): User
    {
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        return $traveler;
    }

    public function test_coordinator_can_save_practical_info_when_editing_a_trip(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs(User::factory()->coordinator()->create())
            ->put(route('coordinator.trips.update', $trip), [
                'name' => $trip->name,
                'start_date' => $trip->start_date->format('Y-m-d'),
                'end_date' => $trip->end_date->format('Y-m-d'),
                'practical_info' => "Verzamelen om 07:00 bij de hoofdingang.\nNoodnummer: 06-12345678",
            ])
            ->assertSessionHas('success');

        $this->assertStringContainsString('hoofdingang', $trip->fresh()->practical_info);
    }

    public function test_coordinator_can_create_a_trip_with_practical_info(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->post(route('coordinator.trips.store'), [
                'name' => 'Praag 2027',
                'start_date' => '2027-04-01',
                'end_date' => '2027-04-03',
                'practical_info' => 'Neem je ID-kaart mee.',
            ])
            ->assertSessionHas('success');

        $this->assertSame('Neem je ID-kaart mee.', Trip::where('name', 'Praag 2027')->value('practical_info'));
    }

    public function test_practical_info_is_optional(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->post(route('coordinator.trips.store'), [
                'name' => 'Praag 2027',
                'start_date' => '2027-04-01',
                'end_date' => '2027-04-03',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(Trip::where('name', 'Praag 2027')->value('practical_info'));
    }

    public function test_edit_form_shows_the_current_practical_info(): void
    {
        $trip = Trip::factory()->create(['practical_info' => 'Hotel Alfama, kamer 12']);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.trips.edit', $trip))
            ->assertOk()
            ->assertSee('Praktische informatie')
            ->assertSee('Hotel Alfama, kamer 12');
    }

    public function test_approved_traveler_sees_practical_info_with_line_breaks(): void
    {
        $trip = Trip::factory()->create(['practical_info' => "Verzamelen om 07:00\nNoodnummer: 06-12345678"]);

        $this->actingAs($this->approvedTraveler($trip))
            ->get(route('traveler.dashboard', $trip))
            ->assertOk()
            ->assertSee('Praktische informatie')
            ->assertSee('Verzamelen om 07:00<br', false)
            ->assertSee('Noodnummer: 06-12345678');
    }

    public function test_html_in_practical_info_is_escaped(): void
    {
        $trip = Trip::factory()->create(['practical_info' => '<script>alert("x")</script>']);

        $this->actingAs($this->approvedTraveler($trip))
            ->get(route('traveler.dashboard', $trip))
            ->assertDontSee('<script>alert', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_empty_practical_info_shows_a_clear_message(): void
    {
        $trip = Trip::factory()->create(['practical_info' => null]);

        $this->actingAs($this->approvedTraveler($trip))
            ->get(route('traveler.dashboard', $trip))
            ->assertSee('De coördinator heeft nog geen praktische informatie toegevoegd.');
    }
}
