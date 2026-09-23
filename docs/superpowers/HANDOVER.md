# Overdracht — waar staat dit werk?

Laatst bijgewerkt: 2026-09-23, na de reisposter-restyling en de snelle handelingen.

## Stand: de mail wordt echt verstuurd

Alle negen reviewpunten zijn af, en sinds 2026-09-23 gaat de mail echt via Gmail
(`MAIL_MAILER=smtp`, met een app-wachtwoord in `.env`). Getest met een echte registratie: de
activatiemail kwam aan en de activatielink werkte.

- `.env` staat in `.gitignore`; het app-wachtwoord gaat dus nooit mee naar de openbare repo.
- Terug naar mails in `storage/logs/laravel.log`: zet `MAIL_MAILER=log` en draai
  `php artisan config:clear`.
- In de lokale database staat een testaccount "Javi Test" van die proef.
- Punt A hieronder (wachttijd van ±2 s bij registreren en wachtwoordherstel) is op 2026-09-23
  **bewust zo gelaten**: de opdrachtgever vindt de wachttijd geen probleem en wil geen wachtrij.

## Reisposter-restyling en snelle handelingen

Ontwerp: `docs/superpowers/specs/2026-09-23-reisposter-restyling-design.md`. Kleuren ongewijzigd.

- **Uiterlijk.** Elke pagina heeft een teal-kop met golfrand (`x-hero`); de kop komt uit de secties
  `title`, `kop`, `eyebrow`, `subtitle` en `hero` (uitleg bovenin `layouts/app.blade.php`).
  Inlogschermen gebruiken `layouts/auth.blade.php`. Nieuwe bouwstenen: `x-activity-card`
  (statusband *Plek vrij* / *Bijna vol* vanaf 75 % / *Vol* / *Deadline voorbij*) en `x-leeg`
  (lege toestand). Instrument Sans wordt lokaal gebundeld via `@fontsource/instrument-sans`.
- **Zonder herladen.** `resources/js/snel.js` verstuurt formulieren met `data-snel="<id>"` op de
  achtergrond en vervangt alleen het blok `#<id>` plus `#meldingen`. Vijf handelingen: inschrijven
  (`#reizen`), activiteit kiezen (`#activiteiten`), keuze annuleren (`#keuzes`), checklist afvinken
  en toevoegen (`#checklist`), goedkeuren/afwijzen (`#aanvragen`). Controllers zijn niet veranderd.
  Gaat er iets mis (netwerk, 419, 500), dan valt het terug op gewoon versturen.
  `tests/Feature/SnelleHandelingenTest.php` bewaakt dat elk formulier binnen zijn blok staat en dat
  het blok ook na de actie (ook in lege toestand) nog bestaat. **Hernoem je een id, pas dan ook
  `data-snel` aan.**
- **Getest in een echte browser** (headless Chrome, tegen een tijdelijke SQLite-database): alle vijf
  handelingen zonder herladen, scrollpositie blijft staan, validatiefout verschijnt in het blok,
  "Annuleren" in de bevestigingsvraag doet niets, geen horizontale scroll op mobiel, geen
  JS-fouten.
- Onderweg gevonden: de validatiemelding van de checklist zei "Het veld **label** is verplicht".
  Het attribuut heet nu "checklist-item" in `lang/nl/validation.php`.
- Suite: **90 tests groen** (na het opruimen van de voorbeeldtest: 89).

## Waar dit op GitHub staat

`https://github.com/Javi-hub2/tripcrew` (openbaar). Al het werk staat op **`main`**: PR #1
(`feature/registratie-mail-reset`) is op 2026-09-23 gemerged met merge-commit `6329811`, daarna is
de branch lokaal en op GitHub verwijderd. Nieuw werk begint op een nieuwe branch vanaf `main`.

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

Gebouwd op de branch `feature/registratie-mail-reset`, inmiddels gemerged naar `main`. Alle acht taken zijn af en gereviewd, en alle negen
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

## Toets aan de briefing: programma en praktische informatie toegevoegd

Bij het naast elkaar leggen van de briefing en de app ontbraken twee gevraagde onderdelen:

- **Programmaonderdelen beheren.** Nieuw tabblad *Programma* in de reisnavigatie
  (`Coordinator/ProgramItemController`). Per dag toevoegen (zonder herladen, blok `#programma`),
  bewerken (ook naar een andere dag van dezelfde reis) en verwijderen. Validatiefouten staan alleen
  onder het formulier van de dag die verstuurd is: elke dag heeft een eigen foutenzak `dag<id>`
  (`StoreProgramItemRequest::prepareForValidation`). `x-field` kreeg daarvoor de props `id` en `bag`.
- **Praktische informatie.** Nieuwe kolom `trips.practical_info` (migratie
  `2026_09_23_000001_…`, alleen toevoegend). De coördinator vult het in bij de reis, de reiziger ziet
  het naast het dagprogramma. Weergave: eerst `e()`, dan `nl2br()`, dus nooit ruwe HTML.
- **Onderweg gevonden:** een reis bewerken waarvan de dagen al bestonden gaf op SQLite een 500
  (`firstOrCreate` op een datum vond de bestaande dag niet door de date-cast, en de unieke index
  weigerde de dubbele). Op MySQL viel het niet op; `.env.example` staat wel op SQLite.
  `TripController::generateDays()` vergelijkt nu in PHP. Test:
  `test_a_trip_whose_days_already_exist_can_be_edited`.

Nog open uit dezelfde toets (keuze van de opdrachtgever): het dagprogramma toont maar één dag, de
checklist wordt door de reiziger zelf gevuld (dus 0 % bij een nieuwe reiziger), een coördinator kan
geen reiziger uitnodigen, er zijn geen eigen 403/404-pagina's, en er is geen voortgangsdashboard.

## Na de oplevering gevonden: 403 na inloggen als coördinator

Opende je als gast (of met een verlopen reizigerssessie) een reizigerspagina en logde je daarna in
als coördinator, dan stuurde `redirect()->intended()` je naar die reizigerspagina en gaf de
rolcontrole een 403. `LoginController` gebruikt de onthouden pagina nu alleen als de
`role:`-middleware van die route de ingelogde rol doorlaat; anders gaat het naar het dashboard.
Tests in `AuthTest`: de coördinator gaat niet naar een onthouden reizigerspagina, en een reiziger
of coördinator gaat wél naar een onthouden pagina van de eigen rol.

## Timing: punt A bewust zo gelaten, punt B opgelost

**A. Zodra echte Gmail-bezorging aan staat, lekt de responstijd weer.**
Bij registreren en bij wachtwoordherstel doet alleen het pad met een bestaand adres een
netwerkverbinding naar Gmail. Dat kost honderden milliseconden, waardoor opnieuw meetbaar wordt of
een adres een account heeft. Met `MAIL_MAILER=log` is er niets aan de hand, maar de mail staat nu op `smtp`.
Oplossen betekent de mails in een wachtrij zetten (`ShouldQueue` plus een permanent draaiende
`artisan queue:work`), wat het opzetten van het project zwaarder maakt. **Besluit 2026-09-23:
bewust zo gelaten**; de wachttijd van ±2 s is geaccepteerd. Heroverwegen als de site online gaat.

**B. Opgelost (2026-09-23): de inlogroute lekte bij een juist wachtwoord op een niet-geactiveerd
account.** `LoginController` gebruikt nu `Auth::attemptWhen()` met `isActivated()` als voorwaarde,
zodat zo'n poging exact het mislukte pad volgt (binnen Laravels Timebox, zonder in- en uitloggen).
Voorheen keerde die poging als "geslaagd" meteen terug uit de Timebox en werd daarna uitgelogd.
Test: `test_not_activated_user_with_correct_password_takes_the_failed_path` (geen Login/Logout-,
wel een Failed-event).

## Ketentest — gedaan

`tests/Feature/FullJourneyTest.php` loopt de hele keten in één test door: registreren → activeren →
inschrijven → coördinator keurt goed → reiziger ziet het dagprogramma → wachtwoord vergeten →
herstellen → inloggen met het nieuwe wachtwoord. Links komen uit de gerenderde mail, formulier-
adressen uit de gerenderde pagina, en inloggen gaat via het echte formulier (geen `actingAs()`),
zodat juist naadfouten opvallen. Gecontroleerd met een mutatie: een kapotte activatielink in
`ActivationMail` laat de test falen. Suite nu **68 tests groen**.

## Overige restpunten uit de eindreview (niet blokkerend)

- Opgelost (2026-09-23): `Coordinator\ActivityController::update()` en `store()` roepen nu ook de
  `ActivityPolicy` aan (`update` en `create`). De tests `test_updating_…` en
  `test_creating_an_activity_goes_through_the_activity_policy` vervangen de policy door een die alles weigert, want met een reiziger was het verschil niet te
  zien (rol-middleware en `StoreActivityRequest` weigeren die al).
- Dode code opgeruimd (2026-09-23): `RegistrationStatus::label()`, `ActivityChoicePolicy::view()`,
  `ActivityPolicy::manage()`, `Activity::isFull()`, de ongebruikte `$trip` in
  `ActivityChoiceController::store()`, de ongebruikte `MustVerifyEmail`-import in `User.php`, en
  `tests/Unit/ExampleTest.php`. Daarmee was `tests/Unit/` leeg; de Unit-suite is uit
  `phpunit.xml` gehaald, anders stopt PHPUnit met "Test directory not found". Maak je later weer
  unit-tests, zet de suite dan terug.
- Opgelost bij de restyling: compacte (`size="sm"`) en neutrale (`variant="ghost"`) knop, `select`
  in `x-field`, eigen focusring (`.focusring`) op de nav, en het lettertype wordt nu echt geladen.

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
