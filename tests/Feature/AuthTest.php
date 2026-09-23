<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** FE-01 happy path */
    public function test_traveler_can_activate_account_and_is_logged_in(): void
    {
        $user = User::factory()->notActivated()->create();

        $this->post("/activeren/{$user->activation_token}", [
            'password' => 'geheim1234',
            'password_confirmation' => 'geheim1234',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNotNull($user->activated_at);
        $this->assertNull($user->activation_token);
        $this->assertTrue(Hash::check('geheim1234', $user->password)); // TE-02: gehasht, niet plain text
        $this->assertAuthenticatedAs($user);
    }

    /** FE-01 foutgeval: wachtwoorden komen niet overeen -> niets opgeslagen */
    public function test_activation_fails_when_passwords_do_not_match(): void
    {
        $user = User::factory()->notActivated()->create();

        $this->post("/activeren/{$user->activation_token}", [
            'password' => 'geheim1234',
            'password_confirmation' => 'anders5678',
        ])->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->activated_at);
        $this->assertGuest();
    }

    /** FE-02 foutgeval: generieke melding */
    public function test_wrong_password_shows_generic_message(): void
    {
        $user = User::factory()->create();

        $this->post('/inloggen', ['email' => $user->email, 'password' => 'fout'])
            ->assertSessionHas('error', 'Onjuiste gegevens.')
            ->assertSessionDoesntHaveErrors(['email', 'password']);

        $this->assertGuest();
    }

    /** FE-01: niet-geactiveerd account kan niet inloggen */
    public function test_not_activated_user_cannot_log_in(): void
    {
        $user = User::factory()->notActivated()->create(['password' => Hash::make('password')]);

        $this->post('/inloggen', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHas('error', 'Onjuiste gegevens.');

        $this->assertGuest();
    }

    /**
     * Eindreview punt B: bij het juiste wachtwoord op een niet-geactiveerd account
     * slaagde Auth::attempt() eerst. Een geslaagde poging keert meteen terug uit
     * Laravels Timebox, een mislukte wordt opgerekt tot de ondergrens, dus de
     * responstijd verried dat het wachtwoord klopte. Nu moet zo'n poging exact het
     * mislukte pad volgen: geen Login en Logout, wel Failed.
     */
    public function test_not_activated_user_with_correct_password_takes_the_failed_path(): void
    {
        Event::fake([Login::class, Logout::class, Failed::class]);
        $user = User::factory()->notActivated()->create(['password' => Hash::make('password')]);

        $this->post('/inloggen', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHas('error', 'Onjuiste gegevens.');

        Event::assertNotDispatched(Login::class);
        Event::assertNotDispatched(Logout::class);
        Event::assertDispatched(Failed::class);
    }

    /**
     * Wie als gast (of met een verlopen sessie) een reizigerspagina opent en daarna als
     * coördinator inlogt, werd door redirect()->intended() naar die reizigerspagina
     * gestuurd en kreeg een 403. De onthouden pagina geldt alleen als de rol hem mag zien.
     */
    public function test_coordinator_is_not_sent_to_a_remembered_traveler_page(): void
    {
        $coordinator = User::factory()->coordinator()->create(['password' => Hash::make('password')]);

        $this->get(route('traveler.registrations.index'))->assertRedirect(route('login'));

        $this->post('/inloggen', ['email' => $coordinator->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_traveler_is_still_sent_to_the_remembered_page(): void
    {
        $traveler = User::factory()->create(['password' => Hash::make('password')]);

        $this->get(route('traveler.registrations.index'))->assertRedirect(route('login'));

        $this->post('/inloggen', ['email' => $traveler->email, 'password' => 'password'])
            ->assertRedirect(route('traveler.registrations.index'));
    }

    public function test_coordinator_is_still_sent_to_a_remembered_coordinator_page(): void
    {
        $coordinator = User::factory()->coordinator()->create(['password' => Hash::make('password')]);

        $this->get(route('coordinator.registrations.index'))->assertRedirect(route('login'));

        $this->post('/inloggen', ['email' => $coordinator->email, 'password' => 'password'])
            ->assertRedirect(route('coordinator.registrations.index'));
    }
}
