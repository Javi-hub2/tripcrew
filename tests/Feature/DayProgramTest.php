<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FE-03: de reiziger kan het programma van elke reisdag bekijken, niet alleen van vandaag. */
class DayProgramTest extends TestCase
{
    use RefreshDatabase;

    private Trip $trip;

    private TripDay $first;

    private TripDay $second;

    private User $traveler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trip = Trip::factory()->create();
        $this->first = TripDay::factory()->create(['trip_id' => $this->trip->id, 'date' => '2030-06-01']);
        $this->second = TripDay::factory()->create(['trip_id' => $this->trip->id, 'date' => '2030-06-02']);
        ProgramItem::factory()->create(['trip_day_id' => $this->first->id, 'title' => 'Aankomst']);
        ProgramItem::factory()->create(['trip_day_id' => $this->second->id, 'title' => 'Museumdag']);

        $this->traveler = User::factory()->create();
        $this->trip->registrations()->attach($this->traveler, ['status' => RegistrationStatus::Approved->value]);
    }

    public function test_without_a_day_the_first_day_is_shown(): void
    {
        $this->actingAs($this->traveler)
            ->get(route('traveler.dashboard', $this->trip))
            ->assertOk()
            ->assertSee('Aankomst')
            ->assertDontSee('Museumdag');
    }

    public function test_traveler_can_open_another_day(): void
    {
        $this->actingAs($this->traveler)
            ->get(route('traveler.dashboard', [$this->trip, $this->second->id]))
            ->assertOk()
            ->assertSee('Museumdag')
            ->assertDontSee('Aankomst');
    }

    public function test_every_day_is_linked_and_the_current_one_is_marked(): void
    {
        $this->actingAs($this->traveler)
            ->get(route('traveler.dashboard', [$this->trip, $this->second->id]))
            ->assertSee(route('traveler.dashboard', [$this->trip, $this->first->id]), false)
            ->assertSee('href="'.route('traveler.dashboard', [$this->trip, $this->second->id]).'"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_a_day_of_another_trip_gives_404(): void
    {
        $foreign = TripDay::factory()->create();

        $this->actingAs($this->traveler)
            ->get(route('traveler.dashboard', [$this->trip, $foreign->id]))
            ->assertNotFound();
    }

    public function test_other_traveler_pages_still_work(): void
    {
        $this->actingAs($this->traveler)->get(route('traveler.my-choices', $this->trip))->assertOk();
        $this->actingAs($this->traveler)->get(route('traveler.activities', $this->trip))->assertOk();
    }
}
