<?php

use App\Http\Controllers\ActivityChoiceController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\Coordinator\ActivityController;
use App\Http\Controllers\Coordinator\ParticipantController;
use App\Http\Controllers\Coordinator\ProgramItemController;
use App\Http\Controllers\Coordinator\RegistrationController;
use App\Http\Controllers\Coordinator\TripChecklistItemController;
use App\Http\Controllers\Coordinator\TripController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TravelerProgramController;
use App\Http\Controllers\TripRegistrationController;
use Illuminate\Support\Facades\Route;

// --- Auth (FE-01, FE-02) ---
// De throttle-limieten zijn gedefinieerd in AppServiceProvider::configureRateLimiters().
Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/inloggen', [LoginController::class, 'show'])->name('login');
    Route::post('/inloggen', [LoginController::class, 'login'])->middleware('throttle:inloggen');
    Route::get('/activeren/{token}', [ActivationController::class, 'show'])->name('activation.show');
    Route::post('/activeren/{token}', [ActivationController::class, 'activate'])->name('activation.activate');
    Route::get('/registreren', [RegisterController::class, 'show'])->name('register');
    Route::post('/registreren', [RegisterController::class, 'store'])->name('register.store')->middleware('throttle:registreren');
    Route::get('/wachtwoord-vergeten', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/wachtwoord-vergeten', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:wachtwoord-vergeten');
    Route::get('/wachtwoord-herstellen/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/wachtwoord-herstellen', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/uitloggen', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // --- Reiziger (FE-03, FE-04, FE-05) ---
    Route::middleware('role:reiziger')->group(function () {
        Route::get('/reizen', [TripRegistrationController::class, 'index'])->name('traveler.registrations.index');
        Route::post('/reizen/{trip}/inschrijven', [TripRegistrationController::class, 'store'])->name('traveler.registrations.store');

        Route::prefix('reizen/{trip}')->name('traveler.')->group(function () {
            // Alleen cijfers voor de dag, zodat /activiteiten en /mijn-keuzes hier niet op vallen.
            Route::get('/{tripDay?}', [TravelerProgramController::class, 'dashboard'])->whereNumber('tripDay')->name('dashboard');
            Route::get('/activiteiten/{tripDay?}', [TravelerProgramController::class, 'activities'])->name('activities');
            Route::get('/mijn-keuzes', [ActivityChoiceController::class, 'myChoices'])->name('my-choices');
            Route::post('/checklist', [ChecklistItemController::class, 'store'])->name('checklist.store');
            Route::patch('/vaste-checklist/{tripChecklistItem}', [ChecklistItemController::class, 'toggleRequired'])->name('required-checklist.toggle');
        });

        Route::post('/activiteiten/{activity}/kiezen', [ActivityChoiceController::class, 'store'])->name('activities.choose');
        Route::delete('/mijn-keuzes/{choice}', [ActivityChoiceController::class, 'destroy'])->name('choices.destroy');
        Route::patch('/checklist/{checklistItem}', [ChecklistItemController::class, 'toggle'])->name('checklist.toggle');
    });

    // --- Coördinator (FE-06, FE-07, FE-08) ---
    Route::middleware('role:coordinator')->prefix('coordinator')->name('coordinator.')->group(function () {
        Route::resource('trips', TripController::class)->except(['show']);

        Route::get('aanvragen', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::patch('aanvragen/{trip}/{user}/goedkeuren', [RegistrationController::class, 'approve'])->name('registrations.approve');
        Route::patch('aanvragen/{trip}/{user}/afwijzen', [RegistrationController::class, 'reject'])->name('registrations.reject');

        Route::prefix('trips/{trip}')->name('trips.')->group(function () {
            Route::get('activiteiten', [ActivityController::class, 'index'])->name('activities.index');
            Route::get('activiteiten/nieuw', [ActivityController::class, 'create'])->name('activities.create');
            Route::post('activiteiten', [ActivityController::class, 'store'])->name('activities.store');
            Route::get('activiteiten/{activity}/bewerken', [ActivityController::class, 'edit'])->name('activities.edit');
            Route::put('activiteiten/{activity}', [ActivityController::class, 'update'])->name('activities.update');
            Route::delete('activiteiten/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

            Route::get('deelnemers', [ParticipantController::class, 'index'])->name('participants.index');

            // Vaste checklistpunten per reis (voltooiingsstatus in het deelnemersoverzicht).
            Route::get('checklist', [TripChecklistItemController::class, 'index'])->name('checklist.index');
            Route::post('checklist', [TripChecklistItemController::class, 'store'])->name('checklist.store');
            Route::delete('checklist/{tripChecklistItem}', [TripChecklistItemController::class, 'destroy'])->name('checklist.destroy');

            // Programmaonderdelen per dag (briefing: "reizen, dagen en programmaonderdelen beheren").
            Route::get('programma', [ProgramItemController::class, 'index'])->name('program.index');
            Route::post('dagen/{tripDay}/programma', [ProgramItemController::class, 'store'])->name('program.store');
            Route::get('programma/{programItem}/bewerken', [ProgramItemController::class, 'edit'])->name('program.edit');
            Route::put('programma/{programItem}', [ProgramItemController::class, 'update'])->name('program.update');
            Route::delete('programma/{programItem}', [ProgramItemController::class, 'destroy'])->name('program.destroy');
        });
    });
});
