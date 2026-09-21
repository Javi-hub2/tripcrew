<?php

namespace App\Exceptions;

use Exception;

// Interne exception om binnen de DB::transaction() van
// ActivityChoiceController::store() de foutmelding naar buiten te dragen
// zonder de transactie te committen (throw = automatische rollback).
class ActivityChoiceRejected extends Exception
{
}
