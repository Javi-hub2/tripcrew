<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Eindreview-punt 5: zonder limiet kan iemand ongelimiteerd wachtwoorden of
 * e-mailadressen aftasten op de openbare formulieren.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const TOO_MANY = 'Te veel pogingen. Wacht een minuut en probeer het opnieuw.';

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'slachtoffer@example.test']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/inloggen', ['email' => 'slachtoffer@example.test', 'password' => 'fout'])
                ->assertSessionHas('error', 'Onjuiste gegevens.');
        }

        $this->post('/inloggen', ['email' => 'slachtoffer@example.test', 'password' => 'fout'])
            ->assertSessionHas('error', self::TOO_MANY);
    }

    public function test_registration_is_blocked_after_five_attempts(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post('/registreren', ['name' => 'Aftaster', 'email' => "adres{$attempt}@example.test"])
                ->assertSessionHas('success');
        }

        $this->post('/registreren', ['name' => 'Aftaster', 'email' => 'adres6@example.test'])
            ->assertSessionHas('error', self::TOO_MANY);
    }

    public function test_password_reset_requests_are_blocked_after_five_attempts(): void
    {
        // Onbekende adressen: de controller doet het Timebox-pad, geen mail.
        foreach (range(1, 5) as $attempt) {
            $this->post('/wachtwoord-vergeten', ['email' => "adres{$attempt}@example.test"])
                ->assertSessionHasNoErrors();
        }

        $this->post('/wachtwoord-vergeten', ['email' => 'adres6@example.test'])
            ->assertSessionHas('error', self::TOO_MANY);
    }
}
