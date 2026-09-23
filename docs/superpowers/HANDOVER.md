# Overdracht — waar staat dit werk?

Laatst bijgewerkt: 2026-09-23, na de scoped hercontrole over de fixdiff.

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

Branch: `feature/registratie-mail-reset`. Alle acht taken zijn af en gereviewd. De eindreview over
de hele branch is gedaan; van de negen punten daaruit staan er nog **drie open** (punt 4, 5 en 6 —
zie hieronder). De suite is groen en de assets zijn gebouwd.

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
| Fixronde na eindreview | punt 1-3 en 7-9 klaar, **4-6 niet, zie hieronder** | `93c99f3`, `e1fb99a`, `0868c35` |

## Stand van de fixronde

De eindreview leverde negen punten op. **Punt 1, 2, 3, 7, 8 en 9 zijn opgelost en gereviewd.**
Teststand: **59 slagen, 0 falen**, en `npm run build` is gedaan.

Wat er in punt 7, 8 en 9 is opgelost (commit `e1fb99a`):

7. **Capaciteit kon onder het aantal bestaande keuzes gezet worden.** `StoreActivityRequest`
   berekent de ondergrens nu uit het aantal gemaakte keuzes (`min:` wordt dat aantal, minimaal 1).
   Bij aanmaken is er geen activiteit in de route, dus blijft de grens 1 en wordt er geen query
   gedaan. De melding noemt het werkelijke aantal. Grensgeval (capaciteit == aantal keuzes) mag.
8. **Deling door nul liet het coördinatoroverzicht crashen.** `coordinator/activities/index`
   gebruikt nu dezelfde bewaking als `traveler/activities`: bij capaciteit 0 is de balk 100%.
9. **De aanvraagdatum werd opgeslagen maar nergens getoond.** Staat nu onder het e-mailadres op
   `coordinator/registrations/index`, in `d-m-Y`, en alleen als `requested_at` gevuld is.

## Hier ga je verder: punt 4, 5 en 6 zijn NOOIT geland

De vorige sessie schreef in deze overdracht dat punt 1 t/m 6 gedaan waren. De hercontrole over
`git diff fb16db9..HEAD` laat zien dat **punt 4, 5 en 6 niet in de code staan**. Punt 1, 2 en 3
staan er wel. Wat er nog moet gebeuren:

**4. Validatiefouten zonder eigen bericht zijn nog Engels.** Er is géén `lang/`-map in het project.
Laravel 11 levert die niet mee, dus alles waarvoor geen eigen `messages()` bestaat komt in het
Engels terug. Publiceer de Nederlandse regels (`php artisan lang:publish` en dan vertalen, of een
eigen `lang/nl/validation.php`) en zet `APP_LOCALE=nl` in `.env`. Test: post een leeg formulier en
verwacht een Nederlandse melding.

**5. Er is geen rate limiting op inloggen en registreren.** `throttle` komt in `routes/web.php`
nergens voor. Zonder limiet kan iemand ongelimiteerd wachtwoorden of e-mailadressen aftasten. Zet
`throttle:...` op de POST-routes van login, registratie en wachtwoordherstel, met een Nederlandse
melding bij te veel pogingen.

**6. De race bij twee coördinatoren is niet afgevangen.**
`Coordinator\RegistrationController::decide()` leest de status en schrijft daarna, zonder transactie
en zonder lock. Twee gelijktijdige beslissingen kunnen beide door de `Pending`-controle glippen; de
laatste schrijver wint en beide coördinatoren zien "gelukt". Los het op met een voorwaardelijke
update in één statement (alleen bijwerken zolang de status nog `pending` is) en beslis op het aantal
geraakte rijen, of met `lockForUpdate()` binnen een transactie.
De bestaande test `test_two_coordinators_deciding_on_the_same_pending_registration_only_one_wins`
dekt dit **niet**: hij doet de twee beslissingen na elkaar en blijft dus ook zonder atomiciteit
groen. Er is een echte gelijktijdigheidstest nodig (of tenminste een test die de conditionele
update aantoont), net als bij TE-05.

Let op: `lockForUpdate()` werkt op MariaDB, niet op de SQLite waarop de tests draaien.

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
