# Registratie, mail, wachtwoordherstel en restyling — implementatieplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reizigers kunnen zich registreren, per mail hun wachtwoord instellen, zich inschrijven voor reizen die de coördinator goedkeurt, en hun wachtwoord herstellen — in een consistent vormgegeven applicatie.

**Architecture:** Voortbouwen op de bestaande activatiestroom (FE-01) in plaats van een tweede wachtwoordmechanisme. De koppeltabel `trip_user` krijgt een statusveld en wordt daarmee een aanvraag met werkstroom. Autorisatie blijft op één plek: `TripPolicy`. De vormgeving gaat eerst naar gedeelde Blade-componenten, zodat de nieuwe schermen er direct op aansluiten.

**Tech Stack:** Laravel 12, PHP 8.2 (XAMPP), MariaDB 10.4, Tailwind CSS 4 via Vite, PHPUnit met SQLite in-memory.

**Spec:** `docs/superpowers/specs/2026-09-21-registratie-mail-reset-restyling-design.md`

## Global Constraints

- PHP draait via `C:\xampp\php\php.exe` — de PHP in WSL mist `ext-xml` en werkt niet.
- Tests draaien met `C:\xampp\php\php.exe artisan test`, standaard op SQLite in-memory.
- Statuskolom is een `string`, geen `enum`: SQLite (de testdatabase) ondersteunt geen
  enum-kolommen en `ALTER TABLE` op enums is in MariaDB onhandig. De toegestane waarden
  worden in PHP afgedwongen door `App\Enums\RegistrationStatus`.
- Alle gebruikerteksten zijn Nederlands.
- Foutmeldingen bij inloggen, registreren en wachtwoordherstel blijven **generiek**: ze
  mogen nooit prijsgeven of een e-mailadres bestaat.
- Er gaat nooit een wachtwoord per e-mail.
- Wie zich zelf registreert krijgt altijd `role = 'reiziger'`.
- FE-09 blijft gelden: elke melding heeft kleur **én** tekst, nooit kleur alleen.
- `.env` bevat straks een Gmail app-wachtwoord en mag niet in Git (staat al in `.gitignore`).
- Commit na elke taak.

## Bestandsoverzicht

**Nieuw:**

| Bestand | Verantwoordelijkheid |
|---|---|
| `app/Enums/RegistrationStatus.php` | toegestane statussen van een inschrijving |
| `app/Http/Controllers/TripRegistrationController.php` | reiziger schrijft zich in |
| `app/Http/Controllers/Coordinator/RegistrationController.php` | coördinator keurt goed/af |
| `app/Http/Controllers/Auth/RegisterController.php` | zelfregistratie |
| `app/Http/Controllers/Auth/PasswordResetController.php` | wachtwoord vergeten/herstellen |
| `app/Http/Requests/RegisterRequest.php` | validatie registratie |
| `app/Http/Requests/ResetPasswordRequest.php` | validatie nieuw wachtwoord |
| `app/Mail/ActivationMail.php` | activatiemail |
| `app/Mail/PasswordResetMail.php` | herstelmail |
| `database/migrations/2026_09_21_000001_add_status_to_trip_user_table.php` | statusvelden |
| `resources/views/components/{card,button,field}.blade.php` | gedeelde UI-bouwstenen |
| `resources/views/auth/{register,forgot-password,reset-password}.blade.php` | nieuwe schermen |
| `resources/views/traveler/register-trip.blade.php` | inschrijven voor een reis |
| `resources/views/coordinator/registrations/index.blade.php` | openstaande aanvragen |
| `resources/views/emails/{layout,activation,password-reset}.blade.php` | mailtemplates |
| `tests/Feature/{RegistrationTest,TripRegistrationTest,PasswordResetTest}.php` | tests |

**Gewijzigd:** `resources/css/app.css`, alle 19 bestaande views, `app/Models/{User,Trip}.php`, `app/Policies/TripPolicy.php`, `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/Coordinator/ParticipantController.php`, `routes/web.php`, `database/seeders/DatabaseSeeder.php`, `tests/Feature/{CoordinatorTest,ActivityChoiceTest}.php`, `.env`, `.env.example`, `README.md`.

**Verwijderd:** `tests/Feature/ExampleTest.php` (Laravel-boilerplate die botst met de `/`-redirect).

---

## Task 1: Ontwerpsysteem — kleurtokens en Blade-componenten

Zonder gedeelde componenten wordt elke knop- en invoerstijl in 19 views apart overgetypt en blijft de restyling niet consistent. Dit gaat daarom vóór de feature-taken: de nieuwe schermen sluiten er dan direct op aan.

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/components/flash-message.blade.php`
- Create: `resources/views/components/card.blade.php`
- Create: `resources/views/components/button.blade.php`
- Create: `resources/views/components/field.blade.php`

**Interfaces:**
- Consumes: niets.
- Produces:
  - `<x-card>...</x-card>` en `<x-card title="Kop">...</x-card>`
  - `<x-button variant="primary|secondary|danger" type="submit">Tekst</x-button>` — rendert een `<button>`; met `href="..."` rendert het een `<a>` met dezelfde stijl
  - `<x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />` — rendert label + input + `@error`-melding
  - CSS-tokens `--color-brand`, `--color-brand-dark`, `--color-sand`, `--color-accent`, `--color-success`, `--color-danger`

- [ ] **Step 1: Kleurtokens toevoegen aan `resources/css/app.css`**

Onder het bestaande `@theme`-blok, zodat Tailwind er klassen als `bg-brand` en `text-accent` van maakt:

```css
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';

    --color-brand: #0F766E;
    --color-brand-dark: #115E59;
    --color-sand: #FDF6EC;
    --color-accent: #EA580C;
    --color-accent-dark: #C2410C;
    --color-success: #15803D;
    --color-danger: #B91C1C;
}
```

- [ ] **Step 2: `resources/views/components/card.blade.php` aanmaken**

```blade
@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-black/5 bg-white p-6 shadow-sm']) }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-semibold text-brand">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
```

- [ ] **Step 3: `resources/views/components/button.blade.php` aanmaken**

```blade
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
    $styles = [
        'primary' => 'bg-accent text-white hover:bg-accent-dark focus-visible:ring-accent',
        'secondary' => 'border border-brand/30 bg-white text-brand hover:bg-brand/5 focus-visible:ring-brand',
        'danger' => 'bg-danger text-white hover:opacity-90 focus-visible:ring-danger',
    ];
    $classes = $base.' '.($styles[$variant] ?? $styles['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
```

- [ ] **Step 4: `resources/views/components/field.blade.php` aanmaken**

```blade
@props(['name', 'label', 'type' => 'text', 'value' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input id="{{ $name }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ $type === 'password' ? '' : $value }}"
           {{ $attributes->merge(['class' => 'mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-brand focus:ring-brand']) }}>
    @error($name)
        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
```

- [ ] **Step 5: Layout omzetten naar het nieuwe palet**

Vervang in `resources/views/layouts/app.blade.php` de `<body>`- en `<nav>`-regels:

```blade
<body class="min-h-screen bg-sand text-slate-800 antialiased">
    <nav class="bg-brand text-white shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-semibold">
                <span aria-hidden="true">&#9992;</span> TripCrew
            </a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    <span>{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg bg-white/15 px-3 py-1.5 font-medium text-white hover:bg-white/25">
                            Uitloggen
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>
```

- [ ] **Step 6: `flash-message` op de nieuwe kleuren zetten**

Structuur (icoon + "Gelukt:"/"Let op:" + tekst) blijft ongewijzigd — FE-09 eist tekst naast kleur. Alleen de klassen wijzigen:

```blade
{{-- FE-09: iedere actie toont een melding met kleur én tekst — nooit kleur alleen. --}}
@if (session('success'))
    <div role="status" class="mb-4 flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-success">
        <span aria-hidden="true">&#10003;</span>
        <span class="font-medium">Gelukt:</span> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-4 flex items-center gap-2 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-danger">
        <span aria-hidden="true">&#9888;</span>
        <span class="font-medium">Let op:</span> {{ session('error') }}
    </div>
@endif
```

- [ ] **Step 7: Bouwen en visueel controleren**

Run: `npm run build`
Expected: build slaagt, `public/build/manifest.json` wordt geschreven.

Run: `C:\xampp\php\php.exe artisan test`
Expected: dezelfde uitslag als voor deze taak (1 failed door `ExampleTest`, rest passed). Deze taak mag geen enkele test breken.

- [ ] **Step 8: Commit**

```bash
git add resources/css/app.css resources/views/layouts resources/views/components
git commit -m "style: kleurtokens en herbruikbare card-, button- en field-componenten"
```

---

## Task 2: Bestaande views omzetten naar de componenten

**Files:**
- Modify: alle views onder `resources/views/auth/`, `resources/views/coordinator/`, `resources/views/traveler/`, plus `resources/views/welcome.blade.php`

**Interfaces:**
- Consumes: `x-card`, `x-button`, `x-field` uit Task 1.
- Produces: geen nieuwe interfaces; alleen consistente opmaak.

- [ ] **Step 1: `auth/login.blade.php` omzetten als referentie voor de rest**

```blade
@extends('layouts.app')
@section('title', 'Inloggen')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Inloggen">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
            <x-field name="password" label="Wachtwoord" type="password" required />
            <x-button type="submit" class="w-full">Inloggen</x-button>
        </form>
    </x-card>
</div>
@endsection
```

De links naar registreren en wachtwoord vergeten komen in Task 6 en 7 hieronder.

- [ ] **Step 2: De overige views omzetten**

Per view: vervang losse `<div class="... bg-white ... shadow">` door `<x-card>`, losse `<button class="...">` door `<x-button>`, en label+input+`@error`-blokken door `<x-field>`. Laat alle teksten, routes, `@if`-logica en lege-staat-meldingen ongewijzigd — dit is opmaak, geen gedragswijziging.

Om te zetten:
`auth/activate.blade.php`, `coordinator/_nav.blade.php`, `coordinator/activities/_form.blade.php`, `coordinator/activities/create.blade.php`, `coordinator/activities/edit.blade.php`, `coordinator/activities/index.blade.php`, `coordinator/participants/index.blade.php`, `coordinator/trips/_form.blade.php`, `coordinator/trips/create.blade.php`, `coordinator/trips/edit.blade.php`, `coordinator/trips/index.blade.php`, `traveler/_tabs.blade.php`, `traveler/activities.blade.php`, `traveler/dashboard.blade.php`, `traveler/my-choices.blade.php`, `welcome.blade.php`.

Let op bij `coordinator/activities/_form.blade.php`: de knop die uitgeschakeld wordt tot de capaciteit klopt (FE-07) moet die `disabled`-logica behouden. `<x-button>` geeft extra attributen door, dus `<x-button :disabled="$disabled">` werkt.

- [ ] **Step 3: Controleren dat er geen losse stijlen achterblijven**

Run: `grep -rn "bg-\[#0C4A6E\]\|bg-\[#06B6D4\]" resources/views/`
Expected: geen resultaten — alle oude hardgecodeerde kleuren zijn weg.

- [ ] **Step 4: Tests draaien**

Run: `C:\xampp\php\php.exe artisan test`
Expected: dezelfde uitslag als voor deze taak. `CoordinatorTest` controleert op zichtbare tekst (`assertSee('Sam Test')`, `assertSee('50%')`) — die teksten moeten dus blijven staan.

- [ ] **Step 5: Commit**

```bash
git add resources/views
git commit -m "style: alle bestaande views op de gedeelde componenten"
```

---

## Task 3: Statusveld op `trip_user` met autorisatie

**Files:**
- Create: `app/Enums/RegistrationStatus.php`
- Create: `database/migrations/2026_09_21_000001_add_status_to_trip_user_table.php`
- Modify: `app/Models/User.php`, `app/Models/Trip.php`, `app/Policies/TripPolicy.php`
- Modify: `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/Coordinator/ParticipantController.php:16`
- Modify: `database/seeders/DatabaseSeeder.php:55`, `tests/Feature/CoordinatorTest.php:45,79,90`, `tests/Feature/ActivityChoiceTest.php:20`
- Test: `tests/Feature/TripRegistrationTest.php`

**Interfaces:**
- Consumes: niets.
- Produces:
  - `RegistrationStatus::Pending|Approved|Rejected` (string-backed enum, waarden `pending`/`approved`/`rejected`)
  - `User::trips(): BelongsToMany` — alle inschrijvingen, met pivot
  - `User::approvedTrips(): BelongsToMany` — alleen goedgekeurde
  - `Trip::registrations(): BelongsToMany` — alle rijen, met pivot; hierop wordt `attach()` gedaan
  - `Trip::travelers(): BelongsToMany` — alleen goedgekeurde deelnemers
  - `Trip::pendingRegistrations(): BelongsToMany` — alleen aanvragen in behandeling

- [ ] **Step 1: Schrijf de falende test**

`tests/Feature/TripRegistrationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_registration_does_not_grant_access_to_the_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $trip))
            ->assertForbidden();
    }

    public function test_approved_registration_grants_access_to_the_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Approved->value]);

        $this->actingAs($traveler)
            ->get(route('traveler.dashboard', $trip))
            ->assertOk();
    }

    public function test_only_approved_travelers_count_as_participants(): void
    {
        $trip = Trip::factory()->create();
        $trip->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Approved->value]);
        $trip->registrations()->attach(User::factory()->create(), ['status' => RegistrationStatus::Pending->value]);

        $this->assertSame(1, $trip->travelers()->count());
        $this->assertSame(1, $trip->pendingRegistrations()->count());
    }
}
```

- [ ] **Step 2: Run de test en zie hem falen**

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: FAIL — `Class "App\Enums\RegistrationStatus" not found`.

- [ ] **Step 3: Enum aanmaken**

`app/Enums/RegistrationStatus.php`:

```php
<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In behandeling',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
        };
    }
}
```

- [ ] **Step 4: Migratie schrijven**

`database/migrations/2026_09_21_000001_add_status_to_trip_user_table.php`:

```php
<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_user', function (Blueprint $table) {
            // string, geen enum: SQLite (de testdatabase) kent geen enum-kolommen.
            $table->string('status')->default(RegistrationStatus::Pending->value)->after('user_id');
            $table->timestamp('requested_at')->nullable()->after('status');
            $table->timestamp('decided_at')->nullable()->after('requested_at');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
        });

        // Bestaande koppelingen (seeder) waren impliciet goedgekeurd.
        DB::table('trip_user')->update([
            'status' => RegistrationStatus::Approved->value,
            'requested_at' => now(),
            'decided_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('trip_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['status', 'requested_at', 'decided_at']);
        });
    }
};
```

- [ ] **Step 5: Relaties op `User` bijwerken**

Vervang in `app/Models/User.php` de methode `trips()` en voeg `approvedTrips()` toe:

```php
    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class)
            ->withPivot(['status', 'requested_at', 'decided_at', 'decided_by'])
            ->withTimestamps();
    }

    public function approvedTrips(): BelongsToMany
    {
        return $this->trips()->wherePivot('status', RegistrationStatus::Approved->value);
    }
```

Voeg bovenaan toe: `use App\Enums\RegistrationStatus;`

- [ ] **Step 6: Relaties op `Trip` bijwerken**

Vervang in `app/Models/Trip.php` de methode `travelers()`:

```php
    /** Alle inschrijvingen, ongeacht status. Hierop wordt attach() gedaan. */
    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'requested_at', 'decided_at', 'decided_by'])
            ->withTimestamps();
    }

    /** Alleen goedgekeurde deelnemers (FE-08). */
    public function travelers(): BelongsToMany
    {
        return $this->registrations()->wherePivot('status', RegistrationStatus::Approved->value);
    }

    public function pendingRegistrations(): BelongsToMany
    {
        return $this->registrations()->wherePivot('status', RegistrationStatus::Pending->value);
    }
```

Voeg bovenaan toe: `use App\Enums\RegistrationStatus;`

`hasParticipants()` blijft `$this->travelers()->exists()` — een reis met alleen openstaande aanvragen mag dus wél verwijderd worden. Dat is de bedoeling.

- [ ] **Step 7: Beide policies bijwerken**

In `app/Policies/TripPolicy.php`, methode `view()`:

```php
        // Reiziger mag alleen een reis zien waarvoor zijn inschrijving is goedgekeurd.
        return $user->approvedTrips()->whereKey($trip->id)->exists();
```

En in `app/Policies/ActivityPolicy.php:37` staat dezelfde lidmaatschapscontrole. Zonder
deze wijziging kan een reiziger met een aanvraag in behandeling alsnog een activiteit
kiezen:

```php
        return $user->approvedTrips()->whereKey($activity->tripDay->trip_id)->exists();
```

Voeg deze test toe aan `tests/Feature/TripRegistrationTest.php`:

```php
    public function test_pending_traveler_cannot_choose_an_activity(): void
    {
        $activity = \App\Models\Activity::factory()->create();
        $trip = $activity->tripDay->trip;
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->post(route('activities.choose', $activity))
            ->assertForbidden();
    }
```

- [ ] **Step 8: Bestaande attach-plekken bijwerken**

Vier plekken koppelen nu zonder status en zouden `pending` worden. Verander ze:

- `database/seeders/DatabaseSeeder.php:55` →
  `$trip->registrations()->attach($travelers->pluck('id')->all(), ['status' => \App\Enums\RegistrationStatus::Approved->value, 'requested_at' => now(), 'decided_at' => now()]);`
- `tests/Feature/CoordinatorTest.php:45,79,90` en `tests/Feature/ActivityChoiceTest.php:20` →
  `$trip->registrations()->attach($traveler, ['status' => \App\Enums\RegistrationStatus::Approved->value]);`
  (regel 45 gebruikt `User::factory()->create()` inline — behoud dat)

- [ ] **Step 9: Dashboard laten doorsturen naar het inschrijfscherm**

In `app/Http/Controllers/DashboardController.php`: vervang `$user->trips()->first()` door `$user->approvedTrips()->first()`. Het `if (! $trip)`-blok wordt in Task 4 aangepast zodra de inschrijfroute bestaat; laat het nu staan.

- [ ] **Step 10: ParticipantController controleren**

`app/Http/Controllers/Coordinator/ParticipantController.php:16` gebruikt `$trip->travelers()` — dat filtert nu vanzelf op goedgekeurd. Geen wijziging nodig; controleer alleen dat de query nog werkt.

- [ ] **Step 11: Migratie draaien en tests**

Run: `C:\xampp\php\php.exe artisan migrate`
Expected: migratie slaagt op de MariaDB-database.

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: PASS (3 tests).

Run: `C:\xampp\php\php.exe artisan test`
Expected: alleen `ExampleTest` faalt nog; `CoordinatorTest` en `ActivityChoiceTest` slagen.

- [ ] **Step 12: Commit**

```bash
git add app database tests
git commit -m "feat: inschrijvingen met status op trip_user, autorisatie via TripPolicy"
```

---

## Task 4: Reiziger schrijft zich in voor een reis

**Files:**
- Create: `app/Http/Controllers/TripRegistrationController.php`
- Create: `resources/views/traveler/register-trip.blade.php`
- Modify: `routes/web.php`, `app/Http/Controllers/DashboardController.php`
- Test: `tests/Feature/TripRegistrationTest.php`

**Interfaces:**
- Consumes: `RegistrationStatus`, `User::approvedTrips()`, `Trip::registrations()` uit Task 3; `x-card`, `x-button` uit Task 1.
- Produces: routes `traveler.registrations.index` (GET `/reizen`) en `traveler.registrations.store` (POST `/reizen/{trip}/inschrijven`).

- [ ] **Step 1: Schrijf de falende tests**

Toevoegen aan `tests/Feature/TripRegistrationTest.php`:

```php
    public function test_traveler_can_register_for_a_trip(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();

        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertRedirect(route('traveler.registrations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Pending->value,
        ]);
    }

    public function test_traveler_can_register_for_multiple_trips(): void
    {
        $traveler = User::factory()->create();
        $first = Trip::factory()->create();
        $second = Trip::factory()->create();

        $this->actingAs($traveler)->post(route('traveler.registrations.store', $first));
        $this->actingAs($traveler)->post(route('traveler.registrations.store', $second));

        $this->assertSame(2, $traveler->trips()->count());
    }

    public function test_registering_twice_does_not_create_a_second_row(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();

        $this->actingAs($traveler)->post(route('traveler.registrations.store', $trip));
        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertSessionHas('error');

        $this->assertSame(1, $traveler->trips()->count());
    }

    public function test_rejected_registration_can_be_submitted_again(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Rejected->value]);

        $this->actingAs($traveler)
            ->post(route('traveler.registrations.store', $trip))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Pending->value,
        ]);
    }

    public function test_coordinator_cannot_use_the_traveler_registration_screen(): void
    {
        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('traveler.registrations.index'))
            ->assertForbidden();
    }
```

- [ ] **Step 2: Run de tests en zie ze falen**

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: FAIL — `Route [traveler.registrations.index] not defined`.

- [ ] **Step 3: Controller schrijven**

`app/Http/Controllers/TripRegistrationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Reiziger schrijft zich in voor een reis; de coördinator beslist.
class TripRegistrationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('traveler.register-trip', [
            'trips' => Trip::orderBy('start_date')->get(),
            // Kolom expliciet kwalificeren: 'status' alleen zou nu toevallig werken
            // omdat trips geen kolom status heeft.
            'registrations' => $user->trips()->pluck('trip_user.status', 'trips.id'),
        ]);
    }

    public function store(Trip $trip): RedirectResponse
    {
        $user = Auth::user();
        $current = $user->trips()->whereKey($trip->id)->first();

        if ($current && $current->pivot->status !== RegistrationStatus::Rejected->value) {
            return back()->with('error', 'Je bent al ingeschreven voor deze reis.');
        }

        $attributes = [
            'status' => RegistrationStatus::Pending->value,
            'requested_at' => now(),
            'decided_at' => null,
            'decided_by' => null,
        ];

        // Een afgewezen aanvraag mag opnieuw ingediend worden.
        $current
            ? $user->trips()->updateExistingPivot($trip->id, $attributes)
            : $user->trips()->attach($trip->id, $attributes);

        return redirect()->route('traveler.registrations.index')
            ->with('success', 'Je aanvraag staat klaar voor de coördinator.');
    }
}
```

- [ ] **Step 4: Routes toevoegen**

In `routes/web.php`, binnen de `role:reiziger`-groep, vóór de `prefix('reizen/{trip}')`-groep (anders vangt `{trip}` de route `/reizen` af):

```php
        Route::get('/reizen', [TripRegistrationController::class, 'index'])->name('traveler.registrations.index');
        Route::post('/reizen/{trip}/inschrijven', [TripRegistrationController::class, 'store'])->name('traveler.registrations.store');
```

Voeg de import toe: `use App\Http\Controllers\TripRegistrationController;`

- [ ] **Step 5: View schrijven**

`resources/views/traveler/register-trip.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Inschrijven voor een reis')

@section('content')
<h1 class="mb-1 text-2xl font-semibold text-brand">Schrijf je in voor een reis</h1>
<p class="mb-6 text-slate-600">De coördinator beoordeelt je aanvraag. Daarna zie je het dagprogramma.</p>

@if ($trips->isEmpty())
    <x-card>
        <p class="text-slate-600">Er staan op dit moment geen reizen open. Kom later terug.</p>
    </x-card>
@else
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($trips as $trip)
            @php $status = $registrations[$trip->id] ?? null; @endphp
            <x-card>
                <h2 class="text-lg font-semibold text-brand">{{ $trip->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $trip->start_date->format('d-m-Y') }} t/m {{ $trip->end_date->format('d-m-Y') }}
                </p>

                <div class="mt-4">
                    @if ($status === \App\Enums\RegistrationStatus::Approved->value)
                        <x-button variant="secondary" :href="route('traveler.dashboard', $trip)">Bekijk het programma</x-button>
                    @elseif ($status === \App\Enums\RegistrationStatus::Pending->value)
                        <p class="text-sm font-medium text-slate-600">In behandeling bij de coördinator.</p>
                    @else
                        @if ($status === \App\Enums\RegistrationStatus::Rejected->value)
                            <p class="mb-2 text-sm text-danger">Je vorige aanvraag is afgewezen. Je mag het opnieuw proberen.</p>
                        @endif
                        <form method="POST" action="{{ route('traveler.registrations.store', $trip) }}">
                            @csrf
                            <x-button type="submit">Inschrijven</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
```

- [ ] **Step 6: Dashboard laten doorsturen naar dit scherm**

In `app/Http/Controllers/DashboardController.php`, vervang het `if (! $trip)`-blok:

```php
        if (! $trip) {
            return redirect()->route('traveler.registrations.index');
        }
```

De oude foutmelding ("Neem contact op met je reiscoördinator") vervalt: de reiziger kan het nu zelf regelen.

- [ ] **Step 7: Tests draaien**

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: PASS (8 tests).

- [ ] **Step 8: Commit**

```bash
git add app resources/views/traveler routes/web.php tests
git commit -m "feat: reiziger kan zich inschrijven voor een of meer reizen"
```

---

## Task 5: Coördinator keurt aanvragen goed of af

**Files:**
- Create: `app/Http/Controllers/Coordinator/RegistrationController.php`
- Create: `resources/views/coordinator/registrations/index.blade.php`
- Modify: `routes/web.php`, `resources/views/coordinator/_nav.blade.php`
- Test: `tests/Feature/TripRegistrationTest.php`

**Interfaces:**
- Consumes: `RegistrationStatus`, `Trip::pendingRegistrations()` uit Task 3.
- Produces: routes `coordinator.registrations.index` (GET `/coordinator/aanvragen`), `coordinator.registrations.approve` en `coordinator.registrations.reject` (beide PATCH `/coordinator/aanvragen/{trip}/{user}/...`).

- [ ] **Step 1: Schrijf de falende tests**

Toevoegen aan `tests/Feature/TripRegistrationTest.php`:

```php
    public function test_coordinator_sees_pending_registrations(): void
    {
        $trip = Trip::factory()->create(['name' => 'Skireis Oostenrijk']);
        $traveler = User::factory()->create(['name' => 'Sam Test']);
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->get(route('coordinator.registrations.index'))
            ->assertOk()
            ->assertSee('Sam Test')
            ->assertSee('Skireis Oostenrijk');
    }

    public function test_coordinator_can_approve_a_registration(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);
        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertRedirect(route('coordinator.registrations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Approved->value,
            'decided_by' => $coordinator->id,
        ]);

        $this->actingAs($traveler)->get(route('traveler.dashboard', $trip))->assertOk();
    }

    public function test_coordinator_can_reject_a_registration(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs(User::factory()->coordinator()->create())
            ->patch(route('coordinator.registrations.reject', [$trip, $traveler]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trip_user', [
            'trip_id' => $trip->id,
            'user_id' => $traveler->id,
            'status' => RegistrationStatus::Rejected->value,
        ]);
    }

    public function test_traveler_cannot_approve_registrations(): void
    {
        $trip = Trip::factory()->create();
        $traveler = User::factory()->create();
        $trip->registrations()->attach($traveler, ['status' => RegistrationStatus::Pending->value]);

        $this->actingAs($traveler)
            ->patch(route('coordinator.registrations.approve', [$trip, $traveler]))
            ->assertForbidden();
    }
```

- [ ] **Step 2: Run de tests en zie ze falen**

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: FAIL — `Route [coordinator.registrations.index] not defined`.

- [ ] **Step 3: Controller schrijven**

`app/Http/Controllers/Coordinator/RegistrationController.php`:

```php
<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// FE-06: de coördinator beslist wie er meegaat.
class RegistrationController extends Controller
{
    public function index(): View
    {
        $trips = Trip::with('pendingRegistrations')
            ->orderBy('start_date')
            ->get()
            ->filter(fn (Trip $trip) => $trip->pendingRegistrations->isNotEmpty());

        return view('coordinator.registrations.index', ['trips' => $trips]);
    }

    public function approve(Trip $trip, User $user): RedirectResponse
    {
        return $this->decide($trip, $user, RegistrationStatus::Approved, "{$user->name} doet mee aan {$trip->name}.");
    }

    public function reject(Trip $trip, User $user): RedirectResponse
    {
        return $this->decide($trip, $user, RegistrationStatus::Rejected, "De aanvraag van {$user->name} is afgewezen.");
    }

    private function decide(Trip $trip, User $user, RegistrationStatus $status, string $message): RedirectResponse
    {
        $trip->registrations()->updateExistingPivot($user->id, [
            'status' => $status->value,
            'decided_at' => now(),
            'decided_by' => Auth::id(),
        ]);

        return redirect()->route('coordinator.registrations.index')->with('success', $message);
    }
}
```

- [ ] **Step 4: Routes toevoegen**

In `routes/web.php`, binnen de bestaande `role:coordinator`-groep met prefix `coordinator`:

```php
        Route::get('aanvragen', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::patch('aanvragen/{trip}/{user}/goedkeuren', [RegistrationController::class, 'approve'])->name('registrations.approve');
        Route::patch('aanvragen/{trip}/{user}/afwijzen', [RegistrationController::class, 'reject'])->name('registrations.reject');
```

Voeg de import toe: `use App\Http\Controllers\Coordinator\RegistrationController;`

- [ ] **Step 5: View schrijven**

`resources/views/coordinator/registrations/index.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Openstaande aanvragen')

@section('content')
@include('coordinator._nav')

<h1 class="mb-6 text-2xl font-semibold text-brand">Openstaande aanvragen</h1>

@if ($trips->isEmpty())
    <x-card>
        <p class="text-slate-600">Er staan geen aanvragen open.</p>
    </x-card>
@else
    <div class="space-y-6">
        @foreach ($trips as $trip)
            <x-card :title="$trip->name">
                <ul class="divide-y divide-slate-100">
                    @foreach ($trip->pendingRegistrations as $traveler)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-medium">{{ $traveler->name }}</p>
                                <p class="text-sm text-slate-600">{{ $traveler->email }}</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('coordinator.registrations.approve', [$trip, $traveler]) }}">
                                    @csrf @method('PATCH')
                                    <x-button type="submit">Goedkeuren</x-button>
                                </form>
                                <form method="POST" action="{{ route('coordinator.registrations.reject', [$trip, $traveler]) }}">
                                    @csrf @method('PATCH')
                                    <x-button type="submit" variant="danger">Afwijzen</x-button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
```

- [ ] **Step 6: Link in de coördinatornavigatie**

Voeg in `resources/views/coordinator/_nav.blade.php` een link toe naar `route('coordinator.registrations.index')` met de tekst `Aanvragen`, in dezelfde opmaak als de bestaande links.

- [ ] **Step 7: Tests draaien**

Run: `C:\xampp\php\php.exe artisan test --filter=TripRegistrationTest`
Expected: PASS (12 tests).

Run: `C:\xampp\php\php.exe artisan test`
Expected: alleen `ExampleTest` faalt nog.

- [ ] **Step 8: Commit**

```bash
git add app resources/views/coordinator routes/web.php tests
git commit -m "feat: coordinator keurt inschrijvingen goed of af"
```

---

## Task 6: Zelfregistratie met activatiemail

**Files:**
- Create: `app/Http/Controllers/Auth/RegisterController.php`, `app/Http/Requests/RegisterRequest.php`
- Create: `app/Mail/ActivationMail.php`
- Create: `resources/views/auth/register.blade.php`, `resources/views/emails/layout.blade.php`, `resources/views/emails/activation.blade.php`
- Modify: `routes/web.php`, `resources/views/auth/login.blade.php`
- Test: `tests/Feature/RegistrationTest.php`

**Interfaces:**
- Consumes: bestaande `ActivationController` (routes `activation.show` / `activation.activate`); `x-card`, `x-button`, `x-field` uit Task 1.
- Produces: routes `register` (GET `/registreren`) en `register.store` (POST `/registreren`); mailable `ActivationMail(User $user)` met publieke property `$user` en de activatie-URL in de template.

- [ ] **Step 1: Schrijf de falende tests**

`tests/Feature/RegistrationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_and_receives_an_activation_mail(): void
    {
        Mail::fake();

        $this->post('/registreren', [
            'name' => 'Nieuwe Reiziger',
            'email' => 'nieuw@example.test',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $user = User::where('email', 'nieuw@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('reiziger', $user->role);
        $this->assertNull($user->activated_at);
        $this->assertNotNull($user->activation_token);

        Mail::assertSent(ActivationMail::class, fn ($mail) => $mail->hasTo('nieuw@example.test'));
    }

    public function test_registering_with_an_existing_email_creates_no_second_account_and_sends_no_mail(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['email' => 'bestaat@example.test']);

        $this->post('/registreren', [
            'name' => 'Iemand Anders',
            'email' => 'bestaat@example.test',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertSame(1, User::where('email', 'bestaat@example.test')->count());
        $this->assertSame($existing->name, $existing->fresh()->name);
        Mail::assertNothingSent();
    }

    public function test_registration_requires_a_name_and_a_valid_email(): void
    {
        $this->post('/registreren', ['name' => '', 'email' => 'geenmail'])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(0, User::count());
    }

    public function test_registered_user_can_set_a_password_and_log_in(): void
    {
        Mail::fake();
        $this->post('/registreren', ['name' => 'Nieuwe Reiziger', 'email' => 'nieuw@example.test']);
        $user = User::where('email', 'nieuw@example.test')->first();

        $this->post("/activeren/{$user->activation_token}", [
            'password' => 'geheim1234',
            'password_confirmation' => 'geheim1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_logged_in_user_cannot_open_the_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/registreren')
            ->assertRedirect(route('dashboard'));
    }
}
```

- [ ] **Step 2: Run de tests en zie ze falen**

Run: `C:\xampp\php\php.exe artisan test --filter=RegistrationTest`
Expected: FAIL — 404 op `/registreren`.

- [ ] **Step 3: `RegisterRequest` schrijven**

`app/Http/Requests/RegisterRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // openbare pagina; de guest-middleware bewaakt de toegang
    }

    public function rules(): array
    {
        // Bewust géén unique-regel: een bestaand adres mag niet uitlekken.
        // De controller handelt dat stil af.
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Dit is geen geldig e-mailadres.',
        ];
    }
}
```

- [ ] **Step 4: Mailable schrijven**

`app/Mail/ActivationMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Stel je wachtwoord in voor TripCrew');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activation',
            with: [
                'name' => $this->user->name,
                'url' => route('activation.show', $this->user->activation_token),
            ],
        );
    }
}
```

- [ ] **Step 5: Mailtemplates schrijven**

`resources/views/emails/layout.blade.php`:

```blade
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:24px;background:#FDF6EC;font-family:Arial,Helvetica,sans-serif;color:#334155;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">
        <div style="background:#0F766E;color:#ffffff;padding:20px 24px;font-size:18px;font-weight:bold;">
            TripCrew
        </div>
        <div style="padding:24px;line-height:1.6;">
            {{ $slot }}
        </div>
        <div style="padding:16px 24px;background:#F8FAFC;font-size:12px;color:#64748B;">
            Je ontvangt deze mail omdat er een TripCrew-account op dit adres is aangevraagd.
        </div>
    </div>
</body>
</html>
```

`resources/views/emails/activation.blade.php`:

```blade
<x-mail::layout>
    <p>Hallo {{ $name }},</p>
    <p>Je account voor TripCrew staat klaar. Stel hieronder je eigen wachtwoord in.</p>
    <p style="margin:24px 0;">
        <a href="{{ $url }}" style="background:#EA580C;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            Wachtwoord instellen
        </a>
    </p>
    <p style="font-size:13px;color:#64748B;">Werkt de knop niet? Kopieer deze link naar je browser:<br>{{ $url }}</p>
</x-mail::layout>
```

De template gebruikt de anonieme component `emails.layout` via `<x-mail::layout>`. Registreer daarvoor de namespace in `app/Providers/AppServiceProvider.php` in `boot()`:

```php
        \Illuminate\Support\Facades\Blade::anonymousComponentNamespace('emails', 'mail');
```

- [ ] **Step 6: Controller schrijven**

`app/Http/Controllers/Auth/RegisterController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Zelfregistratie. Het wachtwoord wordt pas op het activatiescherm ingesteld (FE-01),
// zodat er nooit een wachtwoord per mail gaat.
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $confirmation = 'Bijna klaar. Check je mail om je wachtwoord in te stellen.';

        // Bestaat het adres al, dan gebeurt er niets — maar de bezoeker ziet dezelfde
        // bevestiging. Anders kan iemand via dit formulier uitvissen wie er een account heeft.
        if (User::where('email', $data['email'])->exists()) {
            return redirect()->route('login')->with('success', $confirmation);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'reiziger', // zelfregistratie levert nooit een coördinator op
            'password' => Hash::make(Str::random(40)), // onbruikbaar tot activatie
            'activated_at' => null,
            'activation_token' => Str::random(64),
        ]);

        Mail::to($user->email)->send(new ActivationMail($user));

        return redirect()->route('login')->with('success', $confirmation);
    }
}
```

- [ ] **Step 7: Routes en link toevoegen**

In `routes/web.php`, binnen de bestaande `guest`-groep:

```php
    Route::get('/registreren', [RegisterController::class, 'show'])->name('register');
    Route::post('/registreren', [RegisterController::class, 'store'])->name('register.store');
```

Import: `use App\Http\Controllers\Auth\RegisterController;`

En onderaan het formulier in `resources/views/auth/login.blade.php`, binnen de `x-card`:

```blade
        <p class="mt-4 text-center text-sm text-slate-600">
            Nog geen account?
            <a href="{{ route('register') }}" class="font-medium text-brand underline">Registreren</a>
        </p>
```

- [ ] **Step 8: Registratieview schrijven**

`resources/views/auth/register.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Registreren')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Account aanmaken">
        <p class="mb-4 text-sm text-slate-600">
            Je ontvangt een e-mail waarmee je zelf je wachtwoord instelt.
        </p>
        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <x-field name="name" label="Naam" :value="old('name')" required autofocus />
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required />
            <x-button type="submit" class="w-full">Account aanmaken</x-button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            Heb je al een account?
            <a href="{{ route('login') }}" class="font-medium text-brand underline">Inloggen</a>
        </p>
    </x-card>
</div>
@endsection
```

- [ ] **Step 9: Tests draaien**

Run: `C:\xampp\php\php.exe artisan test --filter=RegistrationTest`
Expected: PASS (5 tests).

- [ ] **Step 10: Commit**

```bash
git add app resources routes/web.php tests
git commit -m "feat: zelfregistratie met activatiemail"
```

---

## Task 7: Wachtwoord vergeten en herstellen

**Files:**
- Create: `app/Http/Controllers/Auth/PasswordResetController.php`, `app/Http/Requests/ResetPasswordRequest.php`
- Create: `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`
- Create: `resources/views/emails/password-reset.blade.php`
- Modify: `app/Models/User.php`, `routes/web.php`, `resources/views/auth/login.blade.php`
- Test: `tests/Feature/PasswordResetTest.php`

**Interfaces:**
- Consumes: Laravels `Password`-broker en de bestaande tabel `password_reset_tokens`; `emails.layout` uit Task 6.
- Produces: routes `password.request` (GET `/wachtwoord-vergeten`), `password.email` (POST `/wachtwoord-vergeten`), `password.reset` (GET `/wachtwoord-herstellen/{token}`), `password.update` (POST `/wachtwoord-herstellen`).

- [ ] **Step 1: Schrijf de falende tests**

`tests/Feature/PasswordResetTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_activated_user_receives_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/wachtwoord-vergeten', ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_message_and_no_mail(): void
    {
        Notification::fake();

        $this->post('/wachtwoord-vergeten', ['email' => 'bestaatniet@example.test'])
            ->assertSessionHas('success', 'Als dit adres bij ons bekend is, ontvang je een e-mail.');

        Notification::assertNothingSent();
    }

    public function test_not_activated_user_receives_no_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->notActivated()->create();

        $this->post('/wachtwoord-vergeten', ['email' => $user->email])
            ->assertSessionHas('success', 'Als dit adres bij ons bekend is, ontvang je een e-mail.');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_the_password_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/wachtwoord-vergeten', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/wachtwoord-herstellen', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'nieuwgeheim123',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('nieuwgeheim123', $user->fresh()->password));
    }

    public function test_invalid_token_is_refused(): void
    {
        $user = User::factory()->create();

        $this->post('/wachtwoord-herstellen', [
            'token' => 'ongeldig-token',
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'nieuwgeheim123',
        ])->assertSessionHas('error');

        $this->assertFalse(Hash::check('nieuwgeheim123', $user->fresh()->password));
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $this->post('/wachtwoord-herstellen', [
            'token' => 'maakt-niet-uit',
            'email' => $user->email,
            'password' => 'nieuwgeheim123',
            'password_confirmation' => 'iets-anders',
        ])->assertSessionHasErrors('password');
    }
}
```

- [ ] **Step 2: Run de tests en zie ze falen**

Run: `C:\xampp\php\php.exe artisan test --filter=PasswordResetTest`
Expected: FAIL — 404 op `/wachtwoord-vergeten`.

- [ ] **Step 3: Nederlandse herstelmail op `User`**

Voeg toe aan `app/Models/User.php`:

```php
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
```

En maak `app/Notifications/ResetPasswordNotification.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false));

        return (new MailMessage)
            ->subject('Nieuw wachtwoord instellen voor TripCrew')
            ->view('emails.password-reset', [
                'name' => $notifiable->name,
                'url' => $url,
                'minutes' => config('auth.passwords.users.expire'),
            ]);
    }
}
```

De tests gebruiken `ResetPassword::class` in `assertSentTo`; deze klasse erft daarvan, dus die assertie blijft kloppen.

- [ ] **Step 4: Mailtemplate schrijven**

`resources/views/emails/password-reset.blade.php`:

```blade
<x-mail::layout>
    <p>Hallo {{ $name }},</p>
    <p>Je hebt een nieuw wachtwoord aangevraagd voor TripCrew.</p>
    <p style="margin:24px 0;">
        <a href="{{ $url }}" style="background:#EA580C;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            Nieuw wachtwoord instellen
        </a>
    </p>
    <p style="font-size:13px;color:#64748B;">
        Deze link verloopt na {{ $minutes }} minuten. Heb je dit niet aangevraagd, dan hoef je niets te doen.
    </p>
</x-mail::layout>
```

- [ ] **Step 5: `ResetPasswordRequest` schrijven**

`app/Http/Requests/ResetPasswordRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // toegang wordt bepaald door het token, niet door een ingelogde user
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'De wachtwoorden komen niet overeen.',
            'password.min' => 'Het wachtwoord moet minstens 8 tekens bevatten.',
        ];
    }
}
```

- [ ] **Step 6: Controller schrijven**

`app/Http/Controllers/Auth/PasswordResetController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const CONFIRMATION = 'Als dit adres bij ons bekend is, ontvang je een e-mail.';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Niet-geactiveerde accounts krijgen geen herstelmail: die horen hun
        // activatielink te gebruiken. De melding blijft wel hetzelfde.
        $user = User::where('email', $data['email'])->first();

        if ($user && $user->isActivated()) {
            Password::sendResetLink($data);
        }

        return back()->with('success', self::CONFIRMATION);
    }

    public function reset(string $token, Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->with('error', 'Deze herstellink is ongeldig of verlopen. Vraag een nieuwe aan.');
        }

        return redirect()->route('login')->with('success', 'Je wachtwoord is aangepast. Log in met je nieuwe wachtwoord.');
    }
}
```

- [ ] **Step 7: Routes en link toevoegen**

In `routes/web.php`, binnen de `guest`-groep:

```php
    Route::get('/wachtwoord-vergeten', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/wachtwoord-vergeten', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/wachtwoord-herstellen/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/wachtwoord-herstellen', [PasswordResetController::class, 'update'])->name('password.update');
```

Import: `use App\Http\Controllers\Auth\PasswordResetController;`

En in `resources/views/auth/login.blade.php`, direct onder het wachtwoordveld:

```blade
            <p class="text-right text-sm">
                <a href="{{ route('password.request') }}" class="text-brand underline">Wachtwoord vergeten?</a>
            </p>
```

- [ ] **Step 8: Views schrijven**

`resources/views/auth/forgot-password.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Wachtwoord vergeten')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Wachtwoord vergeten">
        <p class="mb-4 text-sm text-slate-600">
            Vul je e-mailadres in. Je ontvangt een link om een nieuw wachtwoord in te stellen.
        </p>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <x-field name="email" label="E-mailadres" type="email" :value="old('email')" required autofocus />
            <x-button type="submit" class="w-full">Stuur mij een link</x-button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-medium text-brand underline">Terug naar inloggen</a>
        </p>
    </x-card>
</div>
@endsection
```

`resources/views/auth/reset-password.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Nieuw wachtwoord')

@section('content')
<div class="mx-auto max-w-sm">
    <x-card title="Nieuw wachtwoord instellen">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="E-mailadres" type="email" :value="old('email', $email)" required />
            <x-field name="password" label="Nieuw wachtwoord" type="password" required autofocus />
            <x-field name="password_confirmation" label="Herhaal wachtwoord" type="password" required />
            <x-button type="submit" class="w-full">Wachtwoord opslaan</x-button>
        </form>
    </x-card>
</div>
@endsection
```

- [ ] **Step 9: Tests draaien**

Run: `C:\xampp\php\php.exe artisan test --filter=PasswordResetTest`
Expected: PASS (6 tests).

- [ ] **Step 10: Commit**

```bash
git add app resources routes/web.php tests
git commit -m "feat: wachtwoord vergeten en herstellen met Nederlandse mail"
```

---

## Task 8: Mailconfiguratie, opruimen en documentatie

**Files:**
- Modify: `.env`, `.env.example`, `README.md`
- Delete: `tests/Feature/ExampleTest.php`

**Interfaces:**
- Consumes: alles uit de voorgaande taken.
- Produces: werkende mailbezorging via Gmail zodra het app-wachtwoord is ingevuld.

- [ ] **Step 1: Botsende boilerplate-test verwijderen**

`tests/Feature/ExampleTest.php` verwacht status 200 op `/`, maar `routes/web.php:15` stuurt `/` door naar de loginpagina. Dat is Laravel-boilerplate, geen TripCrew-eis.

```bash
git rm tests/Feature/ExampleTest.php
```

- [ ] **Step 2: Mailinstellingen in `.env.example`**

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=jouw-adres@gmail.com
MAIL_PASSWORD=jouw-app-wachtwoord-van-16-tekens
MAIL_FROM_ADDRESS=jouw-adres@gmail.com
MAIL_FROM_NAME="TripCrew"
```

- [ ] **Step 3: Zelfde blok in `.env`, met `MAIL_MAILER=log` tot het app-wachtwoord er is**

Zet `MAIL_MAILER=log` en laat `MAIL_PASSWORD` leeg. Zo werkt de applicatie volledig en verschijnen de mails in `storage/logs/laravel.log`. Zodra het app-wachtwoord er is: `MAIL_MAILER=smtp` en het wachtwoord invullen.

- [ ] **Step 4: Controleren dat de mail correct wordt opgebouwd**

```bash
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan tinker --execute="Mail::to('test@example.test')->send(new App\Mail\ActivationMail(App\Models\User::factory()->create()));"
```

Run: `grep -c "Wachtwoord instellen" storage/logs/laravel.log`
Expected: minimaal 1 — de mail met de activatieknop staat in het log.

- [ ] **Step 5: README bijwerken**

Voeg aan de tabel "Herleidbaarheid eisen → code" toe:

| Eis | Waar |
|---|---|
| Zelfregistratie | `Auth/RegisterController`, `RegisterRequest`, `Mail/ActivationMail`, `auth/register.blade.php` |
| Inschrijven voor een reis | `TripRegistrationController`, `traveler/register-trip.blade.php` |
| Goedkeuring door coördinator | `Coordinator/RegistrationController`, `coordinator/registrations/index.blade.php` |
| Wachtwoordherstel | `Auth/PasswordResetController`, `Notifications/ResetPasswordNotification` |

En voeg onder "Ontwerpkeuzes die niet letterlijk in de briefing stonden" toe:

- `trip_user` heeft een statusveld (`pending`/`approved`/`rejected`) met `requested_at`,
  `decided_at` en `decided_by`. De koppeling reiziger–reis is daarmee een aanvraag met
  werkstroom in plaats van een directe koppeling. Neem dit op in het technisch ontwerp.
- Zelfregistratie is toegevoegd; de ERD ging uit van accounts die de coördinator aanmaakt.
  Wie zich zelf registreert krijgt altijd de rol `reiziger`.
- Voor Gmail-bezorging is een app-wachtwoord nodig (Google-account → Beveiliging →
  App-wachtwoorden, vereist tweestapsverificatie). Een schoolaccount op Microsoft 365
  blokkeert SMTP en werkt hier niet voor.

- [ ] **Step 6: Volledige testsuite draaien**

Run: `C:\xampp\php\php.exe artisan test`
Expected: alles PASS, geen enkele failure.

- [ ] **Step 7: Frontend bouwen en met de hand nalopen**

```bash
npm run build
C:\xampp\php\php.exe artisan migrate:fresh --seed
C:\xampp\php\php.exe artisan serve
```

Loop na in de browser: registreren → activatielink uit `laravel.log` → wachtwoord instellen →
inloggen → inschrijven voor een reis → als coördinator goedkeuren → als reiziger het
dagprogramma zien → uitloggen → wachtwoord vergeten.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "chore: mailconfiguratie, documentatie en opruimen boilerplate-test"
```

---

## Zelfcontrole van dit plan

- **Dekking van de spec:** deel 1 → Task 3/4/5; deel 2 → Task 6; deel 3 → Task 7; deel 4 →
  Task 1/2; mailconfiguratie en testplan → Task 8.
- **Namen consistent:** `RegistrationStatus`, `Trip::registrations()`, `Trip::travelers()`,
  `Trip::pendingRegistrations()`, `User::trips()`, `User::approvedTrips()` worden overal
  gelijk gebruikt.
- **Openstaand punt voor de opdrachtgever:** het Gmail app-wachtwoord (Task 8, stap 3). Tot
  dat er is draait alles op `MAIL_MAILER=log`.
