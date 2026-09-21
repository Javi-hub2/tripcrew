# Overdracht — waar staat dit werk?

Laatst bijgewerkt: 2026-09-21, tijdens Taak 5.

## Wat wordt hier gebouwd

Vier uitbreidingen op de TripCrew-MVP: inschrijven voor een reis met goedkeuring door de
coördinator, zelfregistratie met activatiemail, wachtwoordherstel, en een samenhangende
vormgeving.

- Ontwerp (bindend): `docs/superpowers/specs/2026-09-21-registratie-mail-reset-restyling-design.md`
- Implementatieplan, 8 taken: `docs/superpowers/plans/2026-09-21-registratie-mail-reset-restyling.md`
- Werkwijze: superpowers subagent-driven-development — per taak een verse subagent, daarna een
  review, daarna eventueel fixrondes, en aan het eind één brede review over de hele branch.

## Waar staat het nu

Branch: `feature/registratie-mail-reset` (niet op `main`, zodat alles terug te draaien is).

| Taak | Status | Commits |
|---|---|---|
| 1 Ontwerpsysteem (tokens + x-card/x-button/x-field) | klaar, review schoon | `f3fad72..64d22c3` |
| 2 Alle bestaande views omgezet | klaar, review schoon | `64d22c3..83866c4` |
| 3 Statusveld op `trip_user` + autorisatie | klaar, review schoon | `83866c4..3ff7507` |
| 4 Reiziger schrijft zich in voor een reis | klaar, review schoon | `3ff7507..a4b7e4b` |
| 5 Coördinator keurt goed/af | code gecommit, review loopt | `a4b7e4b..2a9741b` |
| 6 Registratie + activatiemail | nog niet begonnen | — |
| 7 Wachtwoord vergeten | nog niet begonnen | — |
| 8 Mailconfiguratie, README, opruimen | nog niet begonnen | — |

Teststand bij `2a9741b`: 33 geslaagd, 1 gefaald. Die ene is `tests/Feature/ExampleTest.php`,
Laravel-boilerplate die botst met de redirect van `/` naar de loginpagina; Taak 8 verwijdert hem.

## Taak 5 bleek toch gecommit

De subagent van Taak 5 viel om op een API-limiet, maar dat gebeurde pas tijdens het schrijven van
zijn rapport — zijn code was al gecommit als `2a9741b`. De werkmap is schoon. Zelf nagemeten:
33 tests geslaagd (was 29) en de routes `coordinator.registrations.index`, `.approve` en `.reject`
bestaan. Er is wel geen `task-5-report.md`, dus de review draait op brief en diff alleen.

## Hervatten

Het voortgangsregister staat in `.superpowers/sdd/2026-09-21-registratie-mail-reset-restyling/progress.md`.
Die map is git-ignored maar blijft op schijf staan; hij bevat per taak de commits, de
reviewbevindingen en elke beslissing die onderweg genomen is. Lees dat bestand eerst.

Per taak is er ook een brief (`task-N-brief.md`) en een rapport (`task-N-report.md`) in dezelfde map.

## Hoe je dit draait

```bash
cd /mnt/c/xampp/htdocs/tripcrew
C:\xampp\php\php.exe artisan test        # PHP in WSL mist ext-xml en werkt NIET
C:\xampp\php\php.exe artisan serve       # http://127.0.0.1:8000
npm run build                            # na wijzigingen in views of CSS
```

Database: MariaDB via XAMPP, database `tripcrew`, gebruiker `root` zonder wachtwoord.
Inloggen: `coordinator@tripcrew.test` en `reiziger@tripcrew.test`, wachtwoord `password`.

**Draai geen `migrate:fresh`, `migrate:rollback` of `db:wipe` zonder te vragen** — de
ontwikkeldatabase bevat de gegevens waarmee de opdrachtgever werkt.

## Openstaand voor de opdrachtgever

**Gmail app-wachtwoord.** Nodig in Taak 8 om mails echt te bezorgen. Aanmaken via
Google-account → Beveiliging → App-wachtwoorden (tweestapsverificatie moet aan staan). Gebruik een
persoonlijk Gmail-account, niet het Yuverta-schoolaccount: dat is Microsoft 365 en blokkeert SMTP.
Tot dat wachtwoord er is draait de mail op `MAIL_MAILER=log` en komen de mails in
`storage/logs/laravel.log`.

## Beslissingen die onderweg genomen zijn

Deze staan met volledige onderbouwing in het voortgangsregister. Kort:

1. **Geen git worktree**, maar een branch in de bestaande map — `vendor/`, `node_modules/` en
   `.env` staan niet in Git, dus een worktree levert een niet-draaiende applicatie op.
2. **`ActivityPolicy` meegenomen in Taak 3.** Het plan werkte alleen `TripPolicy` bij, maar
   `ActivityPolicy@choose` deed dezelfde lidmaatschapscontrole. Zonder die wijziging kon een
   reiziger met een aanvraag in behandeling alsnog een activiteit kiezen.
3. **Palet aangepast naar WCAG AA.** Het oorspronkelijke palet haalde de contrasteis uit de spec
   niet: `--color-accent` van `#EA580C` naar `#C2410C`, `--color-accent-dark` naar `#9A3412`,
   `--color-success` naar `#166534`, plus een nieuw `--color-brand-darker: #134E4A` voor de
   hoverstaat van de uitlogknop. Het oranje is daardoor iets donkerder dan de gekozen mockup.
4. **`resources/views/welcome.blade.php` verwijderd** in plaats van omgezet — 277 regels
   onbereikbare Laravel-marketingboilerplate. Terug te halen met
   `git show 7b8ec4e:resources/views/welcome.blade.php`.
5. **Alle rauwe Tailwind-paletkleuren naar tokens**, zodat kleur uit één plek komt.
6. **Race bij dubbel inschrijven afgevangen** met een `catch` op
   `UniqueConstraintViolationException`, zodat dubbelklikken een nette Nederlandse melding geeft
   in plaats van een 500-pagina.
7. **Database opnieuw opgebouwd** op verzoek van de opdrachtgever met `migrate:fresh --seed`.
   Daarbij bleek dat er géén data verloren was gegaan bij een eerdere, per ongeluk uitgevoerde
   rollback: een verse seed geeft exact dezelfde aantallen als de seedercode voorschrijft.

## Geparkeerde punten voor de eindreview

- `x-button` heeft geen compacte of neutrale variant, en `x-field` ondersteunt geen `select`.
  Daardoor blijft er wat handgeschreven markup staan (checklist-toggle, "Annuleer keuze",
  de velden "Dag" en "Capaciteit").
- Nav-links en de uitlogknop in `layouts/app.blade.php` hebben geen eigen focusring; de
  browserstandaard neemt dat waar.
- Tabellen staan nu binnen een kaart mét padding in plaats van randloos, en de
  "Toevoegen"-knop bij de keuzes is groter geworden. Puur visueel, ter beoordeling.
