<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Nodig voor $this->authorize(...) in Laravel 11+.
    use AuthorizesRequests;
}
