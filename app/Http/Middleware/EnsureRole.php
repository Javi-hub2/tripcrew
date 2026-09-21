<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// TE-03: rol serverside afdwingen per routegroep. Gebruik: ->middleware('role:coordinator')
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === $role, 403, 'Je hebt geen toegang tot deze pagina.');

        return $next($request);
    }
}
