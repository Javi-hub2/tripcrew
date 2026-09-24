<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Briefing: duidelijke meldingen bij fouten en acties die niet zijn toegestaan — in huisstijl en in het Nederlands. */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_forbidden_action_shows_a_dutch_page_with_a_way_back(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('coordinator.trips.index'))
            ->assertForbidden()
            ->assertSee('Geen toegang')
            ->assertSee('Je hebt geen toegang tot deze pagina.')
            ->assertSee('Naar het begin')
            ->assertDontSee('Forbidden');
    }

    /** Een policy-weigering (authorize) heeft standaard de Engelse tekst "This action is unauthorized." */
    public function test_policy_refusal_is_shown_in_dutch(): void
    {
        $trip = Trip::factory()->create(); // reiziger is hier niet voor goedgekeurd

        $this->actingAs(User::factory()->create())
            ->get(route('traveler.dashboard', $trip))
            ->assertForbidden()
            ->assertSee('Je hebt geen toegang tot deze pagina of actie.')
            ->assertDontSee('unauthorized');
    }

    public function test_unknown_page_shows_a_dutch_404(): void
    {
        $this->get('/deze-pagina-bestaat-niet')
            ->assertNotFound()
            ->assertSee('Pagina niet gevonden')
            ->assertSee('Naar het begin')
            ->assertDontSee('Not Found');
    }

    public function test_expired_session_shows_a_dutch_419(): void
    {
        Route::get('/_test/419', fn () => abort(419));

        $this->get('/_test/419')
            ->assertStatus(419)
            ->assertSee('Sessie verlopen')
            ->assertDontSee('Page Expired');
    }

    public function test_server_error_shows_a_dutch_500_without_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/500', fn () => throw new \RuntimeException('geheime details'));

        $this->get('/_test/500')
            ->assertStatus(500)
            ->assertSee('Er ging iets mis')
            ->assertDontSee('geheime details')
            ->assertDontSee('Server Error');
    }
}
