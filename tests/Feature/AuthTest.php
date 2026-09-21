<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
