<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toets aan de briefing: "deelnemers en voltooiingsstatus bekijken" had weinig betekenis
 * zolang alleen de reiziger zelf checklistpunten maakte (een nieuwe reiziger stond altijd
 * op 0 %). Nu legt de coördinator per reis vaste punten vast.
 */
class RequiredChecklistTest extends TestCase
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

    private function approvedTraveler(?Trip $trip = null): User
    {
        $traveler = User::factory()->create();
        ($trip ?? $this->trip)->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        return $traveler;
    }

    public function test_coordinator_can_add_and_see_required_items(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.checklist.store', $this->trip), ['label' => 'Paspoort gecontroleerd'])
            ->assertSessionHas('success');

        $this->get(route('coordinator.trips.checklist.index', $this->trip))
            ->assertOk()
            ->assertSee('Paspoort gecontroleerd');
    }

    public function test_empty_required_list_shows_a_clear_message(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.checklist.index', $this->trip))
            ->assertSee('Nog geen vaste checklistpunten voor deze reis.');
    }

    public function test_label_is_required(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('coordinator.trips.checklist.store', $this->trip), ['label' => ''])
            ->assertSessionHasErrors('label');

        $this->assertSame(0, TripChecklistItem::count());
    }

    public function test_coordinator_can_delete_a_required_item(): void
    {
        $item = TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'Tas ingepakt']);

        $this->actingAs($this->coordinator)
            ->delete(route('coordinator.trips.checklist.destroy', [$this->trip, $item]))
            ->assertSessionHas('success');

        $this->assertModelMissing($item);
    }

    public function test_an_item_of_another_trip_gives_404(): void
    {
        $foreign = TripChecklistItem::create(['trip_id' => Trip::factory()->create()->id, 'label' => 'Elders']);

        $this->actingAs($this->coordinator)
            ->delete(route('coordinator.trips.checklist.destroy', [$this->trip, $foreign]))
            ->assertNotFound();

        $this->assertModelExists($foreign);
    }

    public function test_traveler_cannot_manage_required_items(): void
    {
        $traveler = $this->approvedTraveler();

        $this->actingAs($traveler)->get(route('coordinator.trips.checklist.index', $this->trip))->assertForbidden();
        $this->actingAs($traveler)->post(route('coordinator.trips.checklist.store', $this->trip), ['label' => 'X'])->assertForbidden();
    }

    public function test_traveler_sees_required_items_and_can_tick_them_for_themselves_only(): void
    {
        $item = TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'Reisverzekering geregeld']);
        $sam = $this->approvedTraveler();
        $lisa = $this->approvedTraveler();

        $this->actingAs($sam)
            ->get(route('traveler.my-choices', $this->trip))
            ->assertSee('Van de coördinator')
            ->assertSee('Reisverzekering geregeld');

        $this->actingAs($sam)
            ->patch(route('traveler.required-checklist.toggle', [$this->trip, $item]))
            ->assertSessionHas('success');

        $this->assertTrue($item->isDoneBy($sam));
        $this->assertFalse($item->isDoneBy($lisa));

        // Nog een keer = weer uitvinken.
        $this->actingAs($sam)->patch(route('traveler.required-checklist.toggle', [$this->trip, $item]));
        $this->assertFalse($item->isDoneBy($sam));
    }

    public function test_traveler_cannot_tick_items_of_a_trip_they_are_not_approved_for(): void
    {
        $item = TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'Paspoort']);
        $outsider = $this->approvedTraveler(Trip::factory()->create());

        $this->actingAs($outsider)
            ->patch(route('traveler.required-checklist.toggle', [$this->trip, $item]))
            ->assertForbidden();

        $this->assertFalse($item->isDoneBy($outsider));
    }

    public function test_item_must_belong_to_the_trip_in_the_url(): void
    {
        $otherTrip = Trip::factory()->create();
        $foreign = TripChecklistItem::create(['trip_id' => $otherTrip->id, 'label' => 'Elders']);
        $traveler = $this->approvedTraveler();

        $this->actingAs($traveler)
            ->patch(route('traveler.required-checklist.toggle', [$this->trip, $foreign]))
            ->assertNotFound();
    }

    public function test_completion_status_counts_the_required_items(): void
    {
        $a = TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'A']);
        TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'B']);
        $sam = $this->approvedTraveler();
        $sam->update(['name' => 'Sam Vast']);
        $a->completedBy()->attach($sam);
        // Eigen punten tellen niet mee zodra er vaste punten zijn.
        ChecklistItem::factory()->create(['user_id' => $sam->id, 'trip_id' => $this->trip->id, 'checked' => false]);

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.participants.index', $this->trip))
            ->assertSee('Sam Vast')
            ->assertSee('50%');
    }

    public function test_new_traveler_without_ticks_is_at_zero_with_required_items(): void
    {
        TripChecklistItem::create(['trip_id' => $this->trip->id, 'label' => 'A']);
        $this->approvedTraveler()->update(['name' => 'Nieuw Iemand']);

        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.participants.index', $this->trip))
            ->assertSee('Nieuw Iemand')
            ->assertSee('0%');
    }

    public function test_trip_navigation_links_to_the_checklist(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('coordinator.trips.participants.index', $this->trip))
            ->assertSee(route('coordinator.trips.checklist.index', $this->trip), false);
    }
}
