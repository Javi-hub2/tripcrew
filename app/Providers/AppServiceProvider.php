<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    /** Zelfde tekst op alle drie de openbare formulieren. */
    private const TOO_MANY = 'Te veel pogingen. Wacht een minuut en probeer het opnieuw.';

    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->registerBrevoMailer();

        // 'mail' is een door Laravel gereserveerd voorvoegsel: `<x-mail::..>` rendert
        // altijd via view('mail::...'), en Laravel's markdown-mailrenderer overschrijft
        // de hint-paden van die namespace bij elke markdown-mail of MailMessage-notificatie
        // (en herstelt ze nooit). Met 'app-mail' als eigen voorvoegsel botsen we daar niet mee.
        Blade::anonymousComponentNamespace('emails', 'app-mail');

        // Wachtwoordherstel loopt via Laravels ingebouwde ResetPassword-notificatie
        // (nodig zodat Notification::assertSentTo(..., ResetPassword::class) blijft
        // werken: NotificationFake matcht op de exacte klassenaam). We passen alleen
        // de mailinhoud aan via de officiële toMailUsing-hook, i.p.v. een eigen
        // subklasse te maken die door de fake niet als ResetPassword herkend wordt.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->email], false));

            return (new MailMessage)
                ->subject('Nieuw wachtwoord instellen voor TripCrew')
                ->view('emails.password-reset', [
                    'name' => $notifiable->name,
                    'url' => $url,
                    'minutes' => config('auth.passwords.users.expire'),
                ]);
        });
    }

    /**
     * Eindreview-punt 5: zonder limiet kan iemand op de openbare formulieren
     * ongelimiteerd wachtwoorden of e-mailadressen aftasten.
     *
     * Inloggen telt per e-mailadres én IP: daar gaat het om één account dat
     * bestookt wordt, en een limiet op IP alleen zou alle bezoekers achter
     * hetzelfde schoolnetwerk meteen buitensluiten. Registreren en wachtwoord
     * vergeten tellen juist per IP: daar is de aanval het aflopen van veel
     * verschillende adressen, en een teller per adres zou nooit aanslaan.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('inloggen', fn (Request $request) => Limit::perMinute(5)
            ->by('inloggen|'.Str::lower((string) $request->input('email')).'|'.$request->ip())
            ->response(fn () => $this->tooManyAttempts($request)));

        foreach (['registreren', 'wachtwoord-vergeten'] as $formulier) {
            RateLimiter::for($formulier, fn (Request $request) => Limit::perMinute(5)
                ->by($formulier.'|'.$request->ip())
                ->response(fn () => $this->tooManyAttempts($request)));
        }
    }

    /**
     * Railway blokkeert uitgaande SMTP (poort 25, 465 en 587) op de gratis en Hobby-plannen,
     * dus online gaat de mail via de HTTPS-API van Brevo (MAIL_MAILER=brevo). Lokaal blijft
     * het gewoon Gmail-SMTP.
     */
    private function registerBrevoMailer(): void
    {
        Mail::extend('brevo', fn () => (new BrevoTransportFactory)->create(
            new Dsn('brevo+api', 'default', config('services.brevo.key'))
        ));
    }

    private function tooManyAttempts(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('email', 'name'))
            ->with('error', self::TOO_MANY);
    }

}
