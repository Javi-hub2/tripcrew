<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
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
        Blade::anonymousComponentNamespace('emails', 'mail');

        // `<x-mail::..>` is een door Laravel's ComponentTagCompiler hardgecodeerd
        // voorvoegsel: het rendert altijd via view('mail::...'), ongeacht de
        // anonymousComponentNamespace-registratie hierboven. Zonder deze
        // hint-path-registratie faalt dat pas bij het daadwerkelijk renderen
        // van de mail (niet bij het opstarten) met "No hint path defined for [mail]".
        View::addNamespace('mail', resource_path('views/emails'));
    }
}
