# TripCrew — registratie, mail, wachtwoordherstel en restyling

Datum: 2026-09-21
Status: goedgekeurd door opdrachtgever, klaar voor implementatieplan

## Doel

Vier uitbreidingen op de bestaande MVP:

1. Reizigers kunnen zich inschrijven voor een reis; de coördinator keurt goed of wijst af.
2. Nieuwe gebruikers kunnen zich zelf registreren en krijgen een activatiemail.
3. Wachtwoord vergeten met herstelmail.
4. Alle schermen krijgen een samenhangende vormgeving ("frisse reis-look").

## Uitgangspunten

- Bestaande activatiestroom (FE-01) blijft de manier waarop een wachtwoord wordt ingesteld.
  Er gaat nooit een wachtwoord per mail.
- Bestaande foutmeldingen blijven generiek: het formulier mag niet verraden of een
  e-mailadres bestaat.
- Wie zich zelf registreert krijgt altijd de rol `reiziger`. Coördinator worden kan
  alleen via seeder of database.
- Een reiziger mag zich voor meerdere reizen tegelijk inschrijven.

---

## 1. Inschrijven voor een reis met goedkeuring

### Datamodel

De koppeltabel `trip_user` bestaat al (`trip_id`, `user_id`, unieke index). Toevoegen via
een nieuwe migratie:

| Kolom | Type | Toelichting |
|---|---|---|
| `status` | enum(`pending`,`approved`,`rejected`) | standaard `pending` |
| `requested_at` | timestamp | moment van inschrijven |
| `decided_at` | timestamp nullable | moment van goed-/afkeuren |
| `decided_by` | foreignId nullable → users | welke coördinator besliste |

Bestaande rijen (uit de seeder) krijgen `status = approved` zodat de huidige testdata
blijft werken.

### Stromen

**Reiziger**
- Na login zonder goedgekeurde reis: scherm "Schrijf je in voor een reis" met de
  beschikbare reizen en per reis een knop *Inschrijven*.
- Na inschrijven: status `pending`, melding "Je aanvraag staat klaar voor de coördinator."
- Bij `pending` of `rejected` blijven dagprogramma, activiteiten kiezen en checklist
  ontoegankelijk.
- Bij `approved` komt de reiziger direct in het dagprogramma van die reis.
- Met meerdere goedgekeurde reizen kiest de reiziger welke reis hij bekijkt.

**Coördinator**
- Dashboard toont "Openstaande aanvragen (n)" met naam, e-mailadres, reis en datum.
- Per aanvraag: *Goedkeuren* of *Afwijzen*. Beide zetten `decided_at` en `decided_by`.
- Een afgewezen aanvraag kan de reiziger opnieuw indienen (status terug naar `pending`).

### Autorisatie

De statuscontrole hoort in `TripPolicy@view`, niet in elke controller. Reden: een reiziger
met een `pending`-aanvraag kent het reis-id en zou anders via de URL alsnog bij de
activiteiten kunnen. Alle reizigerroutes die een `Trip` binden vallen daarmee onder
dezelfde controle.

Query-scope `approvedTrips()` op `User` voor de plekken waar een lijst wordt opgehaald.

### Te maken

- Migratie `add_status_to_trip_user_table`
- `TripRegistrationController` (`index`, `store`) voor de reiziger
- `Coordinator\TripRegistrationController` (`index`, `approve`, `reject`)
- Views `traveler/register-trip.blade.php`, `coordinator/registrations/index.blade.php`
- Aanpassing `TripPolicy`, `User`, `DashboardController`

---

## 2. Registratie met activatiemail

### Stroom

```
GET  /registreren      registratieformulier (naam + e-mailadres)
POST /registreren      account aanmaken, activated_at = NULL, activation_token gevuld
                       → ActivationMail versturen
                       → bevestigingsscherm "Check je mail"
GET  /activeren/{token}   bestaand scherm: wachtwoord instellen
POST /activeren/{token}   bestaand: wachtwoord hashen, activated_at zetten
```

Het formulier vraagt géén wachtwoord. Dat wordt pas in het activatiescherm ingesteld,
zodat er geen wachtwoord door de mail gaat en de bestaande, geteste `ActivationController`
hergebruikt wordt.

### Bestaand e-mailadres

Bij een al bestaand e-mailadres wordt geen tweede account gemaakt en geen mail verstuurd,
maar de gebruiker ziet exact hetzelfde bevestigingsscherm. Anders kan iemand via het
formulier achterhalen wie er geregistreerd staat. Dit is dezelfde redenering als de
generieke "Onjuiste gegevens." bij het inloggen.

### Validatie (`RegisterRequest`)

- `name`: verplicht, string, max 255
- `email`: verplicht, geldig e-mailadres, max 255

### Te maken

- `RegisterController` (`show`, `store`), `RegisterRequest`
- `Mail\ActivationMail` + template `emails/activation.blade.php`
- View `auth/register.blade.php`
- Routes in de `guest`-groep; link "Nog geen account? Registreren" onder het loginformulier

---

## 3. Wachtwoord vergeten

Gebruikt Laravels ingebouwde password broker; de tabel `password_reset_tokens` staat al in
de database. Eigen Nederlandse schermen en mailtekst in de nieuwe stijl.

```
GET  /wachtwoord-vergeten          formulier met e-mailadres
POST /wachtwoord-vergeten          Password::sendResetLink(), altijd dezelfde melding
GET  /wachtwoord-herstellen/{token} nieuw wachtwoord instellen
POST /wachtwoord-herstellen         Password::reset()
```

- Tokens verlopen na 60 minuten (`config/auth.php`).
- Melding is altijd "Als dit adres bij ons bekend is, ontvang je een e-mail." — ongeacht of
  het adres bestaat.
- Een niet-geactiveerd account krijgt geen herstelmail; dat account hoort de activatielink
  te gebruiken.
- Wachtwoordeisen gelijk aan het activatiescherm (minimaal 8 tekens, bevestiging).

### Te maken

- `Auth\PasswordResetController` (4 methodes), `ResetPasswordRequest`
- Views `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`
- Eigen notificatietekst in het Nederlands

---

## 4. Vormgeving

### Palet

Als tokens in `resources/css/app.css`, zodat één plek de hele app kleurt:

| Token | Waarde | Gebruik |
|---|---|---|
| `--color-brand` | `#0F766E` zeegroen | navigatie, koppen |
| `--color-brand-dark` | `#115E59` | hover |
| `--color-sand` | `#FDF6EC` | paginaachtergrond |
| `--color-accent` | `#EA580C` oranje | primaire knoppen, nadruk |
| `--color-success` | `#15803D` | geslaagd-meldingen |
| `--color-danger` | `#B91C1C` | foutmeldingen |

Contrast van tekst op deze kleuren moet minimaal AA (4.5:1) halen.

### Componenten

Nu staat elke knop- en invoerstijl los overgetypt in elke view. Zonder gedeelde
componenten wordt de restyling niet consistent en is elke latere wijziging 19 bestanden
werk. Daarom eerst:

- `x-card` — witte kaart met schaduw en ronding
- `x-button` — varianten `primary`, `secondary`, `danger`
- `x-field` — label + input + foutmelding in één

Daarna alle 19 bestaande views plus de nieuwe views daarop overzetten. De bestaande
`x-flash-message` (FE-09: icoon + "Gelukt:"/"Let op:" + tekst) blijft functioneel gelijk en
krijgt alleen de nieuwe kleuren.

### Mailtemplates

Eén gedeeld mail-layout met dezelfde kleuren, gebruikt door activatie- en herstelmail.

---

## Mailconfiguratie

Echte bezorging via Gmail SMTP:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=<persoonlijk gmail-adres>
MAIL_PASSWORD=<app-wachtwoord van 16 tekens>
MAIL_FROM_ADDRESS=<zelfde gmail-adres>
MAIL_FROM_NAME=TripCrew
```

Randvoorwaarden voor de opdrachtgever:

- Een **app-wachtwoord** is vereist (Google-account → Beveiliging → App-wachtwoorden).
  Het gewone accountwachtwoord wordt door Google geweigerd. Tweestapsverificatie moet aan
  staan.
- Gebruik een **persoonlijk Gmail-account**, niet het Yuverta-schoolaccount: dat is
  Microsoft 365 en blokkeert SMTP standaard.
- `.env` bevat daarmee een geheim en mag niet in versiebeheer. Staat al in `.gitignore`.

Tot het app-wachtwoord er is wordt met `MAIL_MAILER=log` aangetoond dat de mails correct
worden opgebouwd.

---

## Testen

Per onderdeel een feature-test, in lijn met de bestaande `tests/Feature`:

| Test | Controleert |
|---|---|
| `RegistrationTest` | account wordt aangemaakt, mail verstuurd, geen tweede account bij bestaand adres, geen wachtwoord in de mail |
| `TripRegistrationTest` | inschrijven geeft `pending`; `pending`-reiziger krijgt 403 op activiteiten; na goedkeuren wel toegang; afgewezen aanvraag kan opnieuw |
| `PasswordResetTest` | herstellink werkt, verlopen token afgewezen, niet-geactiveerd account krijgt geen mail, melding is altijd gelijk |
| Bestaande tests | `AuthTest`, `ActivityChoiceTest`, `CoordinatorTest` blijven slagen |

Mails worden getest met `Mail::fake()`, niet door echt te versturen.

De bestaande `tests/Feature/ExampleTest.php` is Laravel-boilerplate die botst met de
`/`-redirect naar de loginpagina en wordt verwijderd.

---

## Gevolgen voor het technisch ontwerp

Op te nemen in de documentatie bij de eerder vastgelegde afwijkingen van de ERD:

- `trip_user` krijgt een statusveld; de koppeling tussen reiziger en reis is daarmee een
  aanvraag met werkstroom, niet langer een directe koppeling.
- Zelfregistratie is toegevoegd. De ERD ging uit van accounts die door de coördinator
  worden aangemaakt.
