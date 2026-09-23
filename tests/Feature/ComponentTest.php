<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_small_button_is_compact(): void
    {
        $this->blade('<x-button size="sm">Kies</x-button>')
            ->assertSee('px-3 py-1.5', false);
    }

    public function test_ghost_button_is_neutral(): void
    {
        $this->blade('<x-button variant="ghost">Terug</x-button>')
            ->assertSee('bg-slate-100', false);
    }

    public function test_field_renders_a_select_with_the_selected_option(): void
    {
        $this->withViewErrors([])->blade('<x-field name="dag" label="Dag" type="select" :options="[1 => \'Ma\', 2 => \'Di\']" value="2" />')
            ->assertSee('<select id="dag"', false)
            ->assertSee('<option value="2" selected>', false)
            ->assertDontSee('<input', false);
    }

    public function test_empty_state_shows_text_with_a_status_role(): void
    {
        $this->blade('<x-leeg>Nog niets hier.</x-leeg>')
            ->assertSee('role="status"', false)
            ->assertSee('Nog niets hier.');
    }

    public function test_auth_pages_use_the_hero_with_a_live_region_for_messages(): void
    {
        $this->get('/inloggen')
            ->assertOk()
            ->assertSee('Samen op reis, alles op één plek')
            ->assertSee('id="meldingen"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_app_pages_have_a_live_region_for_messages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('traveler.registrations.index'))
            ->assertSee('id="meldingen"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_only_coordinators_get_the_coordinator_links_in_the_header(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('traveler.registrations.index'))
            ->assertDontSee(route('coordinator.trips.index'), false);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.registrations.index'))
            ->assertSee(route('coordinator.trips.index'), false);
    }
}
