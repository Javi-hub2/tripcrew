<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
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
}
