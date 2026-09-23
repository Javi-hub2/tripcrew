<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## TripCrew

TripCrew is een schoolreis-applicatie waarmee reizigers zich registreren, zich inschrijven
voor een reis en hun dagprogramma bekijken, en waarmee coördinatoren reizen beheren en
inschrijvingen goedkeuren of afwijzen.

### Inloggen (demo)

Na `php artisan db:seed` bestaan deze testaccounts:

| Rol | E-mail | Wachtwoord |
|---|---|---|
| Coördinator | `coordinator@tripcrew.test` | `password` |
| Reiziger | `reiziger@tripcrew.test` | `password` |

Alleen voor lokaal gebruik; de accounts worden aangemaakt in `database/seeders/DatabaseSeeder.php`.

### Herleidbaarheid eisen → code

| Eis | Waar |
|---|---|
| Zelfregistratie | `Auth/RegisterController`, `RegisterRequest`, `Mail/ActivationMail`, `auth/register.blade.php` |
| Inschrijven voor een reis | `TripRegistrationController`, `traveler/register-trip.blade.php` |
| Goedkeuring door coördinator | `Coordinator/RegistrationController`, `coordinator/registrations/index.blade.php` |
| Programmaonderdelen beheren | `Coordinator/ProgramItemController`, `StoreProgramItemRequest`, `UpdateProgramItemRequest`, `coordinator/program/*.blade.php` |
| Praktische informatie | kolom `trips.practical_info`, `StoreTripRequest`, `coordinator/trips/_form.blade.php`, `traveler/dashboard.blade.php` |
| Wachtwoordherstel | `Auth/PasswordResetController`, `ResetPasswordRequest`, `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`, `AppServiceProvider` (`ResetPassword::toMailUsing()`) |

### Ontwerpkeuzes die niet letterlijk in de briefing stonden

- `trip_user` heeft een statusveld (`pending`/`approved`/`rejected`) met `requested_at`,
  `decided_at` en `decided_by`. De koppeling reiziger–reis is daarmee een aanvraag met
  werkstroom in plaats van een directe koppeling. Neem dit op in het technisch ontwerp.
- Zelfregistratie is toegevoegd; de ERD ging uit van accounts die de coördinator aanmaakt.
  Wie zich zelf registreert krijgt altijd de rol `reiziger`.
- Voor Gmail-bezorging is een app-wachtwoord nodig (Google-account → Beveiliging →
  App-wachtwoorden, vereist tweestapsverificatie). Een schoolaccount op Microsoft 365
  blokkeert SMTP en werkt hier niet voor.
- De maillayout wordt gebruikt via `<x-app-mail::layout>` en niet `<x-mail::layout>`; het
  voorvoegsel `mail` is bij Laravel gereserveerd voor het ingebouwde markdown-mailsysteem
  en wordt daardoor tijdens het renderen van zo'n mail overschreven.
- Bij registratie wordt altijd een wachtwoordhash berekend, ook wanneer het e-mailadres al
  bestaat en er dus niets wordt opgeslagen. Dat is bewust: zonder dat rekenwerk verraadt de
  responstijd of een adres al een account heeft.
- Wachtwoordherstel heeft geen eigen notificatieklasse; Laravels ingebouwde
  `Illuminate\Auth\Notifications\ResetPassword` wordt gebruikt en de mailinhoud wordt
  aangepast via `ResetPassword::toMailUsing()` in `AppServiceProvider`. Dat is bewust:
  Laravels `NotificationFake` matcht in tests op de exacte klassenaam en niet op
  overerving, waardoor een subklasse van `ResetPassword` de tests zou breken. Het
  officiële `toMailUsing()`-extensiepunt levert bovendien minder eigen code op.

### Vormgeving en snelle handelingen

- Stijl "Reisposter": teal-kop met golfrand (`components/hero.blade.php`), kaarten met
  statusband (`components/activity-card.blade.php`), lettertype Instrument Sans (lokaal via
  `@fontsource/instrument-sans`). Kleuren staan als tokens in `resources/css/app.css`.
- Inschrijven, een activiteit kiezen of annuleren, de checklist en goedkeuren/afwijzen werken
  zonder dat de pagina herlaadt: `resources/js/snel.js` verstuurt formulieren met
  `data-snel="<id>"` op de achtergrond en ververst alleen het blok met dat id. Zonder
  JavaScript werkt alles gewoon met herladen. Bewaakt door
  `tests/Feature/SnelleHandelingenTest.php`.
- Na wijzigingen in views, CSS of JS: `npm run build` (of `npm run dev` tijdens het werken).

### Wat er nog open staat

- Mails worden echt verstuurd via Gmail (`MAIL_MAILER=smtp` met een app-wachtwoord in
  `.env`). Met `MAIL_MAILER=log` komen ze in plaats daarvan in `storage/logs/laravel.log`.
- Bewust zo gelaten: met echte SMTP-bezorging duurt registreren (en wachtwoordherstel) voor
  een adres waar een mail naartoe gaat ±2 seconden, voor een bestaand adres niet. Daaraan is
  in principe te meten of een adres een account heeft. De wachttijd is geaccepteerd; wie het
  verschil wil wegnemen, zet de mails in een wachtrij (`ShouldQueue` op de Mailable plus een
  draaiende `artisan queue:work`).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
