<?php

namespace Tests\Feature;

use App\Mail\ActivationMail;
use App\Models\ProgramItem;
use App\Models\Trip;
use App\Models\TripDay;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Eindreview-restpunt: de drie ernstigste bevindingen waren naadfouten tussen
 * onderdelen die elk los wel getest waren. Deze test loopt de hele keten door
 * zoals een gebruiker dat doet: links komen uit de gerenderde mail, formulier-
 * adressen uit de gerenderde pagina, en inloggen gaat via het echte formulier
 * in plaats van actingAs().
 */
class FullJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_traveler_goes_from_registration_to_day_program_to_password_reset(): void
    {
        Mail::fake();
        Notification::fake();

        $coordinator = User::factory()->coordinator()->create(['email' => 'coordinator@example.test']);
        $trip = Trip::factory()->create(['name' => 'Lissabon 2027']);
        $day = TripDay::factory()->create(['trip_id' => $trip->id, 'date' => $trip->start_date]);
        ProgramItem::factory()->create(['trip_day_id' => $day->id, 'title' => 'Welkomstborrel']);

        // 1. Registreren
        $this->post('/registreren', ['name' => 'Sam de Vries', 'email' => 'sam@example.test'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $traveler = User::where('email', 'sam@example.test')->firstOrFail();
        $this->assertFalse($traveler->isActivated());

        // 2. Activeren via de link uit de mail zelf
        $activationUrl = null;
        Mail::assertSent(ActivationMail::class, function (ActivationMail $mail) use (&$activationUrl) {
            $activationUrl = $this->firstLink($mail->render());

            return $mail->hasTo('sam@example.test');
        });

        $activationPage = $this->get($activationUrl)->assertOk();

        $this->post($this->formAction($activationPage->getContent(), 'Activeren en inloggen'), [
            'password' => 'eerstewachtwoord',
            'password_confirmation' => 'eerstewachtwoord',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($traveler);

        // Nog geen goedgekeurde reis: het dashboard stuurt naar het inschrijfscherm.
        $this->get(route('dashboard'))->assertRedirect(route('traveler.registrations.index'));

        // 3. Inschrijven via het formulier op het inschrijfscherm
        $tripsPage = $this->get(route('traveler.registrations.index'))
            ->assertOk()
            ->assertSee('Lissabon 2027');

        $this->post($this->formAction($tripsPage->getContent(), 'Inschrijven'))
            ->assertSessionHas('success');

        // In behandeling geeft nog geen toegang tot het programma.
        $this->get(route('traveler.dashboard', $trip))->assertForbidden();

        $this->logout();

        // 4. De coördinator logt in en keurt goed via het formulier op de aanvragenpagina
        $this->login('coordinator@example.test', 'password');

        $requestsPage = $this->get(route('coordinator.registrations.index'))
            ->assertOk()
            ->assertSee('Sam de Vries');

        $this->patch($this->formAction($requestsPage->getContent(), 'Goedkeuren'))
            ->assertSessionHas('success');

        $this->get(route('coordinator.registrations.index'))->assertSee('Er staan geen aanvragen open.');

        $this->logout();

        // 5. De reiziger logt in met het zelfgekozen wachtwoord en ziet het dagprogramma
        $this->login('sam@example.test', 'eerstewachtwoord');

        $this->get(route('dashboard'))->assertRedirect(route('traveler.dashboard', $trip));
        $this->get(route('traveler.dashboard', $trip))
            ->assertOk()
            ->assertSee('Welkomstborrel');

        $this->logout();

        // 6. Wachtwoord vergeten, herstellen via de link uit de mail
        $this->post('/wachtwoord-vergeten', ['email' => 'sam@example.test'])
            ->assertSessionHas('success');

        $resetUrl = null;
        Notification::assertSentTo($traveler, ResetPassword::class, function (ResetPassword $notification) use ($traveler, &$resetUrl) {
            $resetUrl = $this->firstLink($notification->toMail($traveler)->render());

            return true;
        });

        $resetPage = $this->get($resetUrl)->assertOk();

        $this->post($this->formAction($resetPage->getContent(), 'Wachtwoord opslaan'), [
            'token' => $this->hiddenInput($resetPage->getContent(), 'token'),
            'email' => 'sam@example.test',
            'password' => 'tweedewachtwoord',
            'password_confirmation' => 'tweedewachtwoord',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        // Het oude wachtwoord werkt niet meer, het nieuwe wel.
        $this->post('/inloggen', ['email' => 'sam@example.test', 'password' => 'eerstewachtwoord'])
            ->assertSessionHas('error');
        $this->assertGuest();

        $this->login('sam@example.test', 'tweedewachtwoord');
        $this->assertAuthenticatedAs($traveler);
    }

    private function login(string $email, string $password): void
    {
        $this->post('/inloggen', ['email' => $email, 'password' => $password])
            ->assertRedirect(route('dashboard'));
    }

    private function logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** De eerste link in een mail: de knop waar de ontvanger op klikt. */
    private function firstLink(string $html): string
    {
        $this->assertMatchesRegularExpression('/<a[^>]+href="([^"]+)"/', $html);
        preg_match('/<a[^>]+href="([^"]+)"/', $html, $match);

        return html_entity_decode($match[1]);
    }

    /** Het action-adres van het formulier met de gegeven knoptekst. */
    private function formAction(string $html, string $button): string
    {
        $pattern = '/<form[^>]*action="([^"]+)"[^>]*>(?:(?!<\/form>).)*?'.preg_quote($button, '/').'/s';
        $this->assertMatchesRegularExpression($pattern, $html, "Geen formulier met de knop '{$button}' gevonden.");
        preg_match($pattern, $html, $match);

        return html_entity_decode($match[1]);
    }

    private function hiddenInput(string $html, string $name): string
    {
        $pattern = '/<input[^>]*name="'.preg_quote($name, '/').'"[^>]*value="([^"]*)"/';
        $this->assertMatchesRegularExpression($pattern, $html);
        preg_match($pattern, $html, $match);

        return html_entity_decode($match[1]);
    }
}
