<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCardTest extends TestCase
{
    use RefreshDatabase;

    private function card(int $capacity, int $taken, bool $deadlinePassed = false)
    {
        $activity = Activity::factory()->create([
            'capacity' => $capacity,
            'deadline' => $deadlinePassed ? now()->subHour() : now()->addDay(),
        ]);
        $activity->choices_count = $taken;

        return $this->blade('<x-activity-card :activity="$activity" />', ['activity' => $activity]);
    }

    public function test_activity_with_room_says_so(): void
    {
        $this->card(capacity: 4, taken: 2)
            ->assertSee('Plek vrij')
            ->assertSee('2 van 4 plekken vrij');
    }

    public function test_three_quarters_taken_counts_as_almost_full(): void
    {
        $this->card(capacity: 4, taken: 3)->assertSee('Bijna vol');
    }

    public function test_just_under_three_quarters_still_has_room(): void
    {
        $this->card(capacity: 100, taken: 74)->assertSee('Plek vrij')->assertDontSee('Bijna vol');
    }

    public function test_full_activity_says_full(): void
    {
        $this->card(capacity: 4, taken: 4)->assertSee('Vol')->assertDontSee('Bijna vol');
    }

    public function test_passed_deadline_wins_over_full(): void
    {
        $this->card(capacity: 4, taken: 4, deadlinePassed: true)
            ->assertSee('Deadline voorbij')
            ->assertDontSee('>Vol<', false);
    }

    public function test_zero_capacity_does_not_crash_and_counts_as_full(): void
    {
        $this->card(capacity: 0, taken: 0)->assertSee('Vol');
    }

    public function test_actions_slot_is_rendered(): void
    {
        $activity = Activity::factory()->create();
        $activity->choices_count = 0;

        $this->blade(
            '<x-activity-card :activity="$activity"><x-slot:acties><span>KNOP</span></x-slot:acties></x-activity-card>',
            ['activity' => $activity]
        )->assertSee('KNOP');
    }
}
