<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_activated_user_receives_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/wachtwoord-vergeten', ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_message_and_no_mail(): void
    {
        Notification::fake();

        $this->post('/wachtwoord-vergeten', ['email' => 'bestaatniet@example.test'])
            ->assertSessionHas('success', 'Als dit adres bij ons bekend is, ontvang je een e-mail.');

        Notification::assertNothingSent();
    }

    public function test_not_activated_user_receives_no_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->notActivated()->create();

        $this->post('/wachtwoord-vergeten', ['email' => $user->email])
            ->assertSessionHas('success', 'Als dit adres bij ons bekend is, ontvang je een e-mail.');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_the_password_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/wachtwoord-vergeten', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/wachtwoord-herstellen', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'nieuwgeheim123',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('nieuwgeheim123', $user->fresh()->password));
    }

    public function test_invalid_token_is_refused(): void
    {
        $user = User::factory()->create();

        $this->post('/wachtwoord-herstellen', [
            'token' => 'ongeldig-token',
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'nieuwgeheim123',
        ])->assertSessionHas('error');

        $this->assertFalse(Hash::check('nieuwgeheim123', $user->fresh()->password));
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $this->post('/wachtwoord-herstellen', [
            'token' => 'maakt-niet-uit',
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'iets-anders',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_reset_mail_renders_with_the_reset_link(): void
    {
        $user = User::factory()->create();
        $notification = new ResetPassword('een-test-token');

        $html = $notification->toMail($user)->render();

        $this->assertStringContainsString('een-test-token', $html);
        $this->assertStringContainsString(urlencode($user->email), $html);
        $this->assertStringContainsString('Nieuw wachtwoord instellen', $html);
    }
}
