# Overdracht — waar staat dit werk?

Laatst bijgewerkt: 2026-09-23, na het pushen naar GitHub en het openen van PR #1.

## Hier ga je verder: de mail echt laten versturen

Alle negen reviewpunten zijn af en alles staat op GitHub. Eén stap ligt klaar en wacht op de
opdrachtgever:

1. **Jij (opdrachtgever):** maak een Gmail **app-wachtwoord** aan (Google-account → Beveiliging →
   App-wachtwoorden; tweestapsverificatie moet aan staan). Gebruik een persoonlijk Gmail-account,
   niet het Yuverta-adres: dat is Microsoft 365 en blokkeert SMTP. Zet daarna in
   `C:\xampp\htdocs\tripcrew\.env`:

   ```
   MAIL_MAILER=smtp
   MAIL_USERNAME=jouwadres@gmail.com
   MAIL_PASSWORD=<app-wachtwoord van 16 tekens, zonder spaties>
   MAIL_FROM_ADDRESS=jouwadres@gmail.com
   ```

   De instructie staat ook als commentaar boven `MAIL_MAILER` in `.env` zelf. `.env` staat in
   `.gitignore` en gaat dus nooit mee naar de openbare repo.

2. **Daarna:** `php artisan config:clear`, en een echte registratie doen op een adres dat de
   opdrachtgever noemt. Controleren of de mail aankomt (ook de spammap) en of de activatielink het
   doet. Zolang stap 1 niet gedaan is blijft `MAIL_MAILER=log` en komen de mails in
   `storage/logs/laravel.log` — de flow werkt dan wel, de bezorging niet.

3. Lees vóór die omzetting punt A hieronder: met echte bezorging wordt de responstijd bij
   registreren en wachtwoordherstel weer een signaal of een adres een account heeft.

## Waar dit op GitHub staat

`https://github.com/Javi-hub2/tripcrew` (openbaar). `main` is de MVP plus het ontwerp;
`feature/registratie-mail-reset` heeft alle 28 commits van dit werk en staat open als **PR #1**,
mergebaar zonder conflicten. Na het mergen lokaal `git checkout main && git pull`, anders lopen de
werkmap en GitHub uit de pas.

## Wat wordt hier gebouwd

Vier uitbreidingen op de TripCrew-MVP: inschrijven voor een reis met goedkeuring door de
coördinator, zelfregistratie met activatiemail, wachtwoordherstel, en een samenhangende
vormgeving.

- Ontwerp (bindend): `docs/superpowers/specs/2026-09-21-registratie-mail-reset-restyling-design.md`
- Implementatieplan, 8 taken: `docs/superpowers/plans/2026-09-21-registratie-mail-reset-restyling.md`
- Voortgangsregister met alle bevindingen en beslissingen:
  `.superpowers/sdd/2026-09-21-registratie-mail-reset-restyling/progress.md` (git-ignored, staat wel
  op schijf). Per taak staan daar ook een brief en een rapport.

Werkwijze: per taak eerst bouwen, daarna een review, dan fixrondes, en aan het eind één
brede review over de hele branch.

## Waar staat het nu

Branch: `feature/registratie-mail-reset`. Alle acht taken zijn af en gereviewd, en alle negen
punten uit de eindreview zijn opgelost. De suite is groen (65 tests) en de assets zijn gebouwd.
Wat nu nog openstaat is een keuze, geen gebrek: punt A en B hieronder, en de restpunten.

| Taak | Status | Commits |
|---|---|---|
| 1 Ontwerpsysteem (tokens + x-card/x-button/x-field) | klaar | `f3fad72..64d22c3` |
| 2 Alle bestaande views omgezet | klaar | `64d22c3..83866c4` |
| 3 Statusveld op `trip_user` + autorisatie | klaar | `83866c4..3ff7507` |
| 4 Reiziger schrijft zich in | klaar | `3ff7507..a4b7e4b` |
| 5 Coördinator keurt goed/af | klaar | `a4b7e4b..8529329` |
| 6 Zelfregistratie + activatiemail | klaar | `8529329..0fcf303` |
| 7 Wachtwoord vergeten | klaar | `0fcf303..fb16db9` |
| 8 Mailconfiguratie, README, opruimen | klaar | `9aa90c2..2fad504` |
| Fixronde na eindreview | alle negen punten klaar | `93c99f3`, `e1fb99a`, plus de commits erna |

## Stand van de fixronde

De eindreview leverde negen punten op; **alle negen zijn opgelost**. Teststand: **65 slagen,
0 falen**, en `npm run build` is gedaan.

Wat er in punt 7, 8 en 9 is opgelost (commit `e1fb99a`):

7. **Capaciteit kon onder het aantal bestaande keuzes gezet worden.** `StoreActivityRequest`
   berekent de ondergrens nu uit het aantal gemaakte keuzes (`min:` wordt dat aantal, minimaal 1).
   Bij aanmaken is er geen activiteit in de route, dus blijft de grens 1 en wordt er geen query
   gedaan. De melding noemt het werkelijke aantal. Grensgeval (capaciteit == aantal keuzes) mag.
8. **Deling door nul liet het coördinatoroverzicht crashen.** `coordinator/activities/index`
   gebruikt nu dezelfde bewaking als `traveler/activities`: bij capaciteit 0 is de balk 100%.
9. **De aanvraagdatum werd opgeslagen maar nergens getoond.** Staat nu onder het e-mailadres op
   `coordinator/registrations/index`, in `d-m-Y`, en alleen als `requested_at` gevuld is.

## Punt 4, 5 en 6 — alsnog gedaan

Eerder stond hier dat punt 1 t/m 6 klaar waren; de hercontrole liet zien dat 4, 5 en 6 niet
in de code stonden. Ze zijn daarna alsnog test-first gebouwd:

**4. Nederlandse validatiemeldingen.** `lang/nl/validation.php` bevat de regels die dit project
gebruikt plus `attributes`, zodat `:attribute` "e-mailadres" wordt in plaats van "email". Wat
ontbreekt valt terug op `APP_FALLBACK_LOCALE=en`. `APP_LOCALE=nl` stond al in `.env` en staat nu ook
in `.env.example`. Tests: `tests/Feature/LocalizationTest.php`.

**5. Rate limiting.** Drie benoemde limieten in `AppServiceProvider::configureRateLimiters()`, elk
5 per minuut, aangehaakt met `throttle:` in `routes/web.php`. Inloggen telt per e-mailadres én IP
(daar wordt één account bestookt, en een limiet op IP alleen zou iedereen achter hetzelfde
schoolnetwerk buitensluiten); registreren en wachtwoord-vergeten tellen per IP, want daar is de
aanval juist het aflopen van veel verschillende adressen. Over de limiet volgt een redirect terug
met de melding "Te veel pogingen. Wacht een minuut en probeer het opnieuw.", niet Laravels kale
429-pagina. Tests: `tests/Feature/RateLimitTest.php`.

**6. De race bij twee coördinatoren.** `RegistrationController::decide()` doet nu één
voorwaardelijke UPDATE (`where status = pending`) en beslist op het aantal geraakte rijen; de losse
leescontrole is weg. Dit werkt ook op SQLite, dus er is geen `lockForUpdate()` nodig.
De test `test_a_decision_made_between_lookup_and_write_is_not_overwritten` bootst de race na met
`DB::listen`: zodra de controller de aanvraag opzoekt, beslist een tweede coördinator ertussendoor.
Op de oude implementatie faalt die test, op de nieuwe niet — anders dan de oudere test
`..._only_one_wins`, die de twee beslissingen na elkaar doet en ook zonder atomiciteit groen bleef.

## Twee punten die bewust NIET zijn opgelost — jouw keuze

**A. Zodra echte Gmail-bezorging aan staat, lekt de responstijd weer.**
Bij registreren en bij wachtwoordherstel doet alleen het pad met een bestaand adres een
netwerkverbinding naar Gmail. Dat kost honderden milliseconden, waardoor opnieuw meetbaar wordt of
een adres een account heeft. Met `MAIL_MAILER=log` (de huidige stand) is er niets aan de hand.
Oplossen betekent de mails in een wachtrij zetten (`ShouldQueue` plus een permanent draaiende
`artisan queue:work`), wat het opzetten van het project zwaarder maakt. Dat is een afweging, geen
vanzelfsprekendheid.

**B. De inlogroute lekt ongeveer 20 milliseconden** bij een juist wachtwoord op een
niet-geactiveerd account (`app/Http/Controllers/Auth/LoginController.php:36`; de `isActivated()`-
controle valt buiten Laravels Timebox). Praktisch nauwelijks bruikbaar, want zo'n account heeft een
willekeurig wachtwoord van veertig tekens.

## Ketentest — gedaan

`tests/Feature/FullJourneyTest.php` loopt de hele keten in één test door: registreren → activeren →
inschrijven → coördinator keurt goed → reiziger ziet het dagprogramma → wachtwoord vergeten →
herstellen → inloggen met het nieuwe wachtwoord. Links komen uit de gerenderde mail, formulier-
adressen uit de gerenderde pagina, en inloggen gaat via het echte formulier (geen `actingAs()`),
zodat juist naadfouten opvallen. Gecontroleerd met een mutatie: een kapotte activatielink in
`ActivationMail` laat de test falen. Suite nu **68 tests groen**.

## Overige restpunten uit de eindreview (niet blokkerend)

- `x-button` heeft geen compacte of neutrale variant, `x-field` ondersteunt geen `select`. Daardoor
  staat er nog handgeschreven markup in `traveler/my-choices.blade.php` en
  `coordinator/activities/_form.blade.php`.
- Nav-links en de uitlogknop in `layouts/app.blade.php` hebben geen eigen focusring; de
  browserstandaard werkt wel.
- `Coordinator\ActivityController::update()` mist een `authorize()`-aanroep die de andere methodes
  wel hebben. Functioneel afgedekt door de rol-middleware.
- Dode code: `RegistrationStatus::label()`, `ActivityChoicePolicy::view()`, `ActivityPolicy::manage()`,
  `Activity::isFull()`, een ongebruikte `$trip` in `ActivityChoiceController:39`, een ongebruikte
  import in `User.php`, en `tests/Unit/ExampleTest.php`.
- `resources/css/app.css:10` verwijst naar het lettertype 'Instrument Sans' dat nergens geladen wordt;
  de app rendert in het systeemlettertype.

## Hoe je dit draait

```bash
cd /mnt/c/xampp/htdocs/tripcrew
C:\xampp\php\php.exe artisan test        # PHP in WSL mist ext-xml en werkt NIET
C:\xampp\php\php.exe artisan serve
npm run build                            # na wijzigingen in views of CSS
```

Werk vanuit `/mnt/c/xampp/htdocs/tripcrew`, niet vanuit de oude kopie onder OneDrive. Gebruik tijdens
het bewerken van views of CSS `npm run dev` (Vite-watcher), dan worden de assets vanzelf opnieuw
gebouwd.

Database: MariaDB via XAMPP, database `tripcrew`, gebruiker `root` zonder wachtwoord.
Inloggen: `coordinator@tripcrew.test` en `reiziger@tripcrew.test`, wachtwoord `password`.

**Draai geen `migrate:fresh`, `migrate:rollback` of `db:wipe` zonder te vragen.**

## Online zetten via Cloudflare

De site draait nu alleen op XAMPP en gaat later via Cloudflare het internet op. Wat er dan moet
gebeuren:

1. In `.env`: `APP_ENV=production`, `APP_DEBUG=false`, en `APP_URL=https://<jouw domein>`.
2. `php artisan config:cache` (en `route:cache`, `view:cache`) na elke `.env`-wijziging.
3. De afzender verhuizen naar het eigen domein: een transactionele maildienst (Brevo, Resend) met
   DNS-verificatie via Cloudflare in plaats van een persoonlijk Gmail-adres. Gmail blijft werken,
   maar mail van een privéadres aan onbekenden belandt vaker in spam.
4. Lees punt A hierboven opnieuw: met echte bezorging is het responstijdlek bij registreren en
   wachtwoordherstel weer meetbaar. Dat is het moment om de mails in een wachtrij te zetten.

**Al geregeld, niet weghalen:** `bootstrap/app.php` vertrouwt de `X-Forwarded-*`-headers
(`trustProxies(at: '*')`). Cloudflare beëindigt https en stuurt het request intern als http door;
zonder dat vertrouwen bouwt `route()` in `ActivationMail` een link met `http://` en de interne host,
en mailen we bezoekers een kapotte activatielink. `tests/Feature/ActivationLinkTest.php` dekt beide
kanten af: met proxyheaders wordt de link `https://<domein>/...`, zonder blijft hij gewoon http.
Staat de app achter iets anders dan Cloudflare, beperk `at:` dan tot de IP-reeksen van die proxy.

## Openstaand voor de opdrachtgever

**Gmail app-wachtwoord.** Zet het in `.env` bij `MAIL_PASSWORD`, zet `MAIL_USERNAME` en
`MAIL_FROM_ADDRESS` op hetzelfde Gmail-adres, en zet dan `MAIL_MAILER=smtp` (daarna
`php artisan config:clear`). Aanmaken
via Google-account → Beveiliging → App-wachtwoorden (tweestapsverificatie moet aan staan). Gebruik
een persoonlijk Gmail-account, niet het Yuverta-schoolaccount: dat is Microsoft 365 en blokkeert
SMTP. Tot dan komen de mails in `storage/logs/laravel.log`. Lees daarbij punt A hierboven.

## Beslissingen die onderweg genomen zijn

Alle met onderbouwing in het voortgangsregister. Kort:

1. **Geen git worktree**, maar een branch in de bestaande map — `vendor/`, `node_modules/` en `.env`
   staan niet in Git, dus een worktree levert een niet-draaiende applicatie op.
2. **`ActivityPolicy` meegenomen in Taak 3**; het plan werkte alleen `TripPolicy` bij, waardoor een
   reiziger met een aanvraag in behandeling alsnog een activiteit had kunnen kiezen.
3. **Palet aangepast naar WCAG AA**: accent `#EA580C` → `#C2410C`, accent-dark → `#9A3412`, success
   → `#166534`, plus `--color-brand-darker: #134E4A`. Het oranje is daardoor iets donkerder dan de
   mockup die je koos.
4. **`welcome.blade.php` verwijderd** — 277 regels onbereikbare Laravel-boilerplate. Terug te halen
   met `git show 7b8ec4e:resources/views/welcome.blade.php`.
5. **Alle rauwe Tailwind-paletkleuren naar tokens.**
6. **Race bij dubbel inschrijven afgevangen**, zodat dubbelklikken een nette melding geeft.
7. **Database opnieuw opgebouwd** op jouw verzoek; daarbij bleek dat er géén data verloren was gegaan
   bij een eerdere, per ongeluk uitgevoerde rollback.
8. **Twee keer een timinglek gerepareerd** (registratie en wachtwoordherstel) door op alle paden even
   duur rekenwerk te doen. Beide keren zat de fout in het oorspronkelijke plan.
9. **Mailnamespace `app-mail` in plaats van `mail`**, omdat `mail` bij Laravel gereserveerd is en door
   markdown-mails overschreven wordt.
10. **Geen eigen `ResetPasswordNotification`-klasse** maar `ResetPassword::toMailUsing()`, omdat
    Laravels NotificationFake op exacte klassenaam matcht en niet op overerving.
