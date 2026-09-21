<?php

namespace Tests\Feature;

use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const CONFIRMATION = 'Bijna klaar. Check je mail om je wachtwoord in te stellen.';

    public function test_visitor_can_register_and_receives_an_activation_mail(): void
    {
        Mail::fake();

        $this->post('/registreren', [
            'name' => 'Nieuwe Reiziger',
            'email' => 'nieuw@example.test',
        ])->assertRedirect(route('login'))->assertSessionHas('success', self::CONFIRMATION);

        $user = User::where('email', 'nieuw@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('reiziger', $user->role);
        $this->assertNull($user->activated_at);
        $this->assertNotNull($user->activation_token);

        Mail::assertSent(ActivationMail::class, fn ($mail) => $mail->hasTo('nieuw@example.test'));
    }

    public function test_registering_with_an_existing_activated_email_creates_no_second_account_and_sends_no_mail(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['email' => 'bestaat@example.test']);
        $this->assertTrue($existing->isActivated());

        $this->post('/registreren', [
            'name' => 'Iemand Anders',
            'email' => 'bestaat@example.test',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success', self::CONFIRMATION);

        $this->assertSame(1, User::where('email', 'bestaat@example.test')->count());
        $this->assertSame($existing->name, $existing->fresh()->name);
        Mail::assertNothingSent();
    }

    /**
     * Regressietest voor het CRITICAL-punt uit de eindreview: registreert iemand
     * opnieuw op een adres waarvan het account nog nooit geactiveerd is (de eerste
     * activatiemail is bv. nooit aangekomen), dan mag het adres niet voorgoed
     * onbruikbaar blijven. Er moet een nieuw token komen en de activatiemail moet
     * opnieuw verstuurd worden, met dezelfde bevestigingstekst als alle andere paden.
     */
    public function test_registering_with_an_existing_unactivated_email_resends_the_activation_mail_with_a_new_token(): void
    {
        Mail::fake();
        $existing = User::factory()->notActivated()->create(['email' => 'nogniet@example.test']);
        $oldToken = $existing->activation_token;

        $this->post('/registreren', [
            'name' => 'Iemand Anders',
            'email' => 'nogniet@example.test',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success', self::CONFIRMATION);

        $this->assertSame(1, User::where('email', 'nogniet@example.test')->count());
        $existing->refresh();
        $this->assertNull($existing->activated_at);
        $this->assertNotNull($existing->activation_token);
        $this->assertNotSame($oldToken, $existing->activation_token);

        Mail::assertSent(ActivationMail::class, fn ($mail) => $mail->hasTo('nogniet@example.test'));
    }

    public function test_registration_requires_a_name_and_a_valid_email(): void
    {
        $this->post('/registreren', ['name' => '', 'email' => 'geenmail'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(0, User::count());
    }

    public function test_registered_user_can_set_a_password_and_log_in(): void
    {
        Mail::fake();
        $this->post('/registreren', ['name' => 'Nieuwe Reiziger', 'email' => 'nieuw@example.test']);
        $user = User::where('email', 'nieuw@example.test')->first();

        $this->post("/activeren/{$user->activation_token}", [
            'password' => 'geheim1234',
            'password_confirmation' => 'geheim1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_logged_in_user_cannot_open_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/registreren')
            ->assertRedirect(route('dashboard'));
    }

    public function test_activation_mail_renders_with_the_activation_link(): void
    {
        $user = User::factory()->notActivated()->create();

        $html = (new ActivationMail($user))->render();

        $this->assertStringContainsString($user->activation_token, $html);
        $this->assertStringContainsString('Wachtwoord instellen', $html);
        $this->assertStringNotContainsString('password', strtolower(strip_tags($html)));
    }

    public function test_registering_cannot_set_a_privileged_role(): void
    {
        Mail::fake();

        $this->post('/registreren', [
            'name' => 'Slimme Reiziger',
            'email' => 'slim@example.test',
            'role' => 'coordinator',
        ]);

        $this->assertSame('reiziger', User::where('email', 'slim@example.test')->first()->role);
    }
}
