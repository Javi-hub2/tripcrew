<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Briefing: coördinator beheert "reizen, dagen en programmaonderdelen". */
class ProgramItemTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private Trip $trip;

    private TripDay $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinator = User::factory()->coordinator()->create();
        $this->trip = Trip::factory()->create();
        $this->day = TripDay::factory()->create(['trip_id' => $this->trip->id, 'date' => '2030-06-01']);
    }

    public function test_coordinator_sees_the_program_per_day(): void
    {
        ProgramItem::factory()->create(['trip_day_id' => $this->day->id, 'time' => '09:00', 'title' => 'Ontbijt', 'location' => 'Hostel']);
        TripDay::factory()->create(['trip_id' => $this->trip->id, 'date' => '2030-06-02']);

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.program.index', $this->trip))
            ->assertOk()
            ->assertSee('Ontbijt')
            ->assertSee('09:00')
            ->assertSee('Hostel')
            ->assertSee('Nog geen programmaonderdelen op deze dag.');
    }

    public function test_coordinator_can_add_an_item_to_a_day(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.program.store', [$this->trip, $this->day]), [
                'time' => '10:30',
                'title' => 'Stadswandeling',
                'location' => 'Centrum',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $item = $this->day->programItems()->first();
        $this->assertSame('Stadswandeling', $item->title);
        $this->assertSame('10:30', substr($item->time, 0, 5));
        $this->assertSame('Centrum', $item->location);
    }

    public function test_location_is_optional(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.program.store', [$this->trip, $this->day]), [
                'time' => '10:30',
                'title' => 'Vrije tijd',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->day->programItems()->first()->location);
    }

    public function test_invalid_input_is_refused_with_errors_for_that_day_only(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.program.store', [$this->trip, $this->day]), [
                'time' => '25:99',
                'title' => '',
            ])
            ->assertSessionHasErrors(['time', 'title'], null, 'dag'.$this->day->id);

        $this->assertSame(0, ProgramItem::count());
    }

    public function test_a_day_of_another_trip_gives_404(): void
    {
        $otherDay = TripDay::factory()->create();

        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.program.store', [$this->trip, $otherDay]), [
                'time' => '10:30',
                'title' => 'Stiekem',
            ])
            ->assertNotFound();

        $this->assertSame(0, ProgramItem::count());
    }

    public function test_coordinator_can_edit_an_item_and_move_it_to_another_day(): void
    {
        $item = ProgramItem::factory()->create(['trip_day_id' => $this->day->id, 'time' => '09:00', 'title' => 'Ontbijt']);
        $secondDay = TripDay::factory()->create(['trip_id' => $this->trip->id, 'date' => '2030-06-02']);

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.program.edit', [$this->trip, $item]))
            ->assertOk()
            ->assertSee('value="09:00"', false);

        $this->put(route('coordinator.trips.program.update', [$this->trip, $item]), [
            'trip_day_id' => $secondDay->id,
            'time' => '08:15',
            'title' => 'Vroeg ontbijt',
            'location' => '',
        ])->assertRedirect(route('coordinator.trips.program.index', $this->trip))->assertSessionHas('success');

        $item->refresh();
        $this->assertSame($secondDay->id, $item->trip_day_id);
        $this->assertSame('Vroeg ontbijt', $item->title);
        $this->assertSame('08:15', substr($item->time, 0, 5));
    }

    public function test_an_item_cannot_be_moved_to_a_day_of_another_trip(): void
    {
        $item = ProgramItem::factory()->create(['trip_day_id' => $this->day->id]);
        $otherDay = TripDay::factory()->create();

        $this->actingAs($this->coordinator)
            ->put(route('coordinator.trips.program.update', [$this->trip, $item]), [
                'trip_day_id' => $otherDay->id,
                'time' => '08:15',
                'title' => 'Verplaatst',
            ])
            ->assertSessionHasErrors('trip_day_id');

        $this->assertSame($this->day->id, $item->fresh()->trip_day_id);
    }

    public function test_coordinator_can_delete_an_item(): void
    {
        $item = ProgramItem::factory()->create(['trip_day_id' => $this->day->id]);

        $this->actingAs($this->coordinator)
            ->delete(route('coordinator.trips.program.destroy', [$this->trip, $item]))
            ->assertSessionHas('success');

        $this->assertModelMissing($item);
    }

    public function test_an_item_of_another_trip_gives_404(): void
    {
        $foreign = ProgramItem::factory()->create();

        $this->actingAs($this->coordinator)
            ->delete(route('coordinator.trips.program.destroy', [$this->trip, $foreign]))
            ->assertNotFound();

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.program.edit', [$this->trip, $foreign]))
            ->assertNotFound();

        $this->assertModelExists($foreign);
    }

    public function test_traveler_cannot_manage_the_program(): void
    {
        $traveler = User::factory()->create();
        $this->trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);
        $item = ProgramItem::factory()->create(['trip_day_id' => $this->day->id]);

        $this->actingAs($traveler)->get(route('coordinator.trips.program.index', $this->trip))->assertForbidden();
        $this->actingAs($traveler)->post(route('coordinator.trips.program.store', [$this->trip, $this->day]), ['time' => '10:00', 'title' => 'X'])->assertForbidden();
        $this->actingAs($traveler)->delete(route('coordinator.trips.program.destroy', [$this->trip, $item]))->assertForbidden();

        $this->assertModelExists($item);
    }

    public function test_added_item_appears_in_the_travelers_day_program(): void
    {
        $traveler = User::factory()->create();
        $this->trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.program.store', [$this->trip, $this->day]), [
                'time' => '19:00',
                'title' => 'Diner met de groep',
            ]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $this->trip))
            ->assertSee('Diner met de groep')
            ->assertSee('19:00');
    }

    public function test_trip_navigation_links_to_the_program(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.activities.index', $this->trip))
            ->assertSee(route('coordinator.trips.program.index', $this->trip), false);
    }
}
