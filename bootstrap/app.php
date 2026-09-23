<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Straks staat de site achter Cloudflare: die beëindigt https en stuurt het
        // request intern als http door. Zonder de X-Forwarded-headers te vertrouwen
        // bouwt route() links met http:// en de interne host — en dan mailen we
        // bezoekers een kapotte activatielink. Zie ActivationLinkTest.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        // Niet-ingelogde gebruikers naar de (Nederlandstalige) loginroute.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
