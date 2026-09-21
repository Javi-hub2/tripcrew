<?php

namespace App\Providers;

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
    }
}
