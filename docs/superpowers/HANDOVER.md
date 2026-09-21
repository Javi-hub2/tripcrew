# Overdracht — waar staat dit werk?

Laatst bijgewerkt: 2026-09-21, tijdens de fixronde na de eindreview.

## Wat wordt hier gebouwd

Vier uitbreidingen op de TripCrew-MVP: inschrijven voor een reis met goedkeuring door de
coördinator, zelfregistratie met activatiemail, wachtwoordherstel, en een samenhangende
vormgeving.

- Ontwerp (bindend): `docs/superpowers/specs/2026-09-21-registratie-mail-reset-restyling-design.md`
- Implementatieplan, 8 taken: `docs/superpowers/plans/2026-09-21-registratie-mail-reset-restyling.md`
- Voortgangsregister met alle bevindingen en beslissingen:
  `.superpowers/sdd/2026-09-21-registratie-mail-reset-restyling/progress.md` (git-ignored, staat wel
  op schijf). Per taak staan daar ook een brief en een rapport.

Werkwijze: per taak een verse subagent, daarna een review, dan fixrondes, en aan het eind één
brede review over de hele branch.

## Waar staat het nu

Branch: `feature/registratie-mail-reset`. Alle acht taken zijn af en gereviewd.
De eindreview over de hele branch is gedaan en de fixronde daarna is **halverwege afgebroken**.

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
| Fixronde na eindreview | **onaf, zie hieronder** | `93c99f3` (wip) |

## Hier ga je morgen verder: de fixronde afmaken

Commit `93c99f3` is bewust een WIP-commit. Teststand: **56 slagen, 3 falen**. Die drie falen
met opzet — het zijn test-first tests waarvan de implementatie nog moet komen:

1. `CoordinatorTest > capacity cannot be lowered below the number of existing choices`
2. `CoordinatorTest > activities index does not crash when capacity is zero`
3. `TripRegistrationTest > coordinator sees the request date in a dutch format`

De eindreview leverde negen punten op. **Punt 1 tot en met 6 zijn gedaan**, punt 7, 8 en 9 niet:

**7. Capaciteit kan onder het aantal bestaande keuzes gezet worden.**
`app/Http/Requests/StoreActivityRequest.php:26` valideert `capacity` alleen op `min:1`. Een
coördinator kan de capaciteit van een activiteit met tien deelnemers op twee zetten; dan staan er
tien mensen op twee plekken. Valideer bij het BEWERKEN van een bestaande activiteit dat `capacity`
niet lager is dan het aantal gemaakte keuzes, met een Nederlandse melding die het werkelijke aantal
noemt. Bij aanmaken verandert er niets.

**8. Deling door nul laat een coördinatorscherm crashen.**
`resources/views/coordinator/activities/index.blade.php:32` rekent
`round($activity->choices_count / $activity->capacity * 100)` zonder bewaking.
`resources/views/traveler/activities.blade.php:35` doet dat wél — neem die bewaking over.

**9. De aanvraagdatum wordt opgeslagen maar nergens getoond.**
Het ontwerp vraagt op het aanvragenscherm om naam, e-mailadres, reis én datum.
`resources/views/coordinator/registrations/index.blade.php` toont alleen naam en e-mail, terwijl
`requested_at` wel gevuld wordt. Toon de datum in Nederlands formaat.

Daarna: `npm run build`, volledige suite groen, committen, en dan nog één scoped hercontrole over
de fixdiff (`git diff fb16db9..HEAD`).

## Wat er in punt 1 tot en met 6 is opgelost

1. **Een nooit-geactiveerd account was permanent onbruikbaar.** Kwam de activatiemail niet aan, dan
   hielp opnieuw registreren niet (adres bestaat al, dus geen mail) en wachtwoord-vergeten ook niet
   (dat weigert niet-geactiveerde accounts). Nu stuurt een tweede registratie op een
   niet-geactiveerd adres een nieuwe activatiemail, met exact dezelfde bevestigingstekst.
2. De coördinator kon `/coordinator/aanvragen` niet bereiken vanaf zijn startpagina.
3. De reiziger had geen enkele link naar `/reizen` en kon zich dus nooit voor een tweede reis
   inschrijven, terwijl de backend dat wel toestaat.
4. Validatiefouten zonder eigen bericht waren Engels; er is nu een Nederlandse `lang/nl`.
5. Geen rate limiting op inloggen en registreren.
6. Race bij twee coördinatoren die tegelijk over dezelfde aanvraag beslissen.

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
- Er is geen test die de hele keten in één keer doorloopt (registreren → activeren → inloggen →
  inschrijven → goedkeuren → dagprogramma → wachtwoord vergeten). Juist de drie ernstigste
  bevindingen van de eindreview waren naadfouten die zo'n test zou hebben gevonden.

## Hoe je dit draait

```bash
cd /mnt/c/xampp/htdocs/tripcrew
C:\xampp\php\php.exe artisan test        # PHP in WSL mist ext-xml en werkt NIET
C:\xampp\php\php.exe artisan serve
npm run build                            # na wijzigingen in views of CSS
```

Database: MariaDB via XAMPP, database `tripcrew`, gebruiker `root` zonder wachtwoord.
Inloggen: `coordinator@tripcrew.test` en `reiziger@tripcrew.test`, wachtwoord `password`.

**Draai geen `migrate:fresh`, `migrate:rollback` of `db:wipe` zonder te vragen.**

## Openstaand voor de opdrachtgever

**Gmail app-wachtwoord.** Zet het in `.env` bij `MAIL_PASSWORD` en zet `MAIL_MAILER=smtp`. Aanmaken
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
   duur rekenwerk te doen. Beide keren zat de fout in mijn eigen plan.
9. **Mailnamespace `app-mail` in plaats van `mail`**, omdat `mail` bij Laravel gereserveerd is en door
   markdown-mails overschreven wordt.
10. **Geen eigen `ResetPasswordNotification`-klasse** maar `ResetPassword::toMailUsing()`, omdat
    Laravels NotificationFake op exacte klassenaam matcht en niet op overerving.
