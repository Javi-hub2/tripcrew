# Reisposter-restyling en snelle handelingen — ontwerp

Datum: 2026-09-23 · Branch: `feature/registratie-mail-reset`

## Doel

TripCrew moet er veel levendiger uitzien, in dezelfde kleuren, en de vijf snelle handelingen moeten
werken zonder dat de pagina herlaadt. Gekozen stijlrichting: **A · Reisposter** (teal-kop met
kleurverloop en golfrand, kaarten met gekleurde band).

**Buiten scope:** paginanavigatie blijft gewoon herladen; geen wachtrij voor mail (punt A blijft
bewust zoals het is); geen nieuwe kleuren; geen wijzigingen aan controllers, routes of database.

## 1. Ontwerpsysteem

Kleurtokens in `resources/css/app.css` blijven ongewijzigd (`brand`, `brand-dark`, `brand-darker`,
`sand`, `accent`, `accent-dark`, `success`, `danger`).

| Onderdeel | Wijziging |
|---|---|
| Lettertype | Instrument Sans wordt echt geladen, lokaal gebundeld via npm (`@fontsource/instrument-sans`), gewichten 400/600/700. |
| `x-hero` (nieuw) | Teal-kop, verloop `brand` → `brand-darker`, golfrand onderaan in `sand`. Slots: `nav` en de standaardslot. De kopteksten komen uit de secties `title`/`kop`, `eyebrow`, `subtitle` en `hero` van de pagina (zie `layouts/app`). |
| `x-leeg` (nieuw) | Lege toestand ("nog niets hier") met `role="status"`, tekst plus icoon. |
| `layouts/app` | Navigatie (logo, naam, uitloggen, coördinator-links) zit in de hero. Inhoud valt deels over de golfrand. Nav-links en uitlogknop krijgen een eigen focusring. |
| `layouts/auth` (nieuw) | Gecentreerde kaart die over de hero valt, met ondertitel "Samen op reis, alles op één plek". Voor inloggen, registreren, wachtwoord vergeten, herstellen en activeren. |
| `x-card` | `rounded-2xl`, zachte teal-schaduw. |
| `x-button` | Oranje gloed op `primary`, indrukeffect (`active:scale`), nieuwe prop `size` (`md` standaard, `sm` compact) en variant `ghost` (neutraal). Vervangt de handgeschreven knoppen in `traveler/my-choices` en `coordinator/activities/_form`. |
| `x-field` | Afgeronde velden, teal focusring; ondersteunt `type="select"` met een `options`-prop. |
| `x-activity-card` (nieuw) | Statusband + beginletter-badge + capaciteitsbalk. Status: *Plek vrij* (teal), *Bijna vol* bij ≥ 75 % bezet (oranje), *Vol* en *Deadline voorbij* (grijs). Voorrang bij meerdere: *Deadline voorbij* > *Vol* > *Bijna vol* > *Plek vrij*. Status staat altijd als tekst in de band, nooit alleen als kleur (FE-09). Slot `acties` voor de knoppen onderaan: *Kies* bij de reiziger, *Bewerken*/*Verwijderen* bij de coördinator. Gebruikt door `traveler/activities` en `coordinator/activities/index`. |
| Tabbladen en dagkeuze | Pillen; actief = `accent`, `aria-current="page"` blijft. |
| `x-flash-message` | Zelfde inhoud en rollen, steviger vorm (linkerrand, icoon in cirkel). |
| Beweging | Hover-lift op kaarten, capaciteitsbalken lopen vol bij laden. Alles uit onder `prefers-reduced-motion: reduce`. |

## 2. Snelle handelingen: `resources/js/snel.js`

Een formulier met `data-snel="<id>"` wordt onderschept:

1. `fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })`.
   De methode-spoofing (`_method`) en het CSRF-token zitten al in de formulierdata.
2. `fetch` volgt de redirect van de controller; het antwoord is de volledige nieuwe pagina.
   Controllers die `back()` gebruiken werken ook: `fetch` stuurt de `Referer` standaard mee.
3. Uit die pagina worden het element `#<id>` en het meldingenblok `#meldingen` gehaald en vervangen
   op de huidige pagina. Scrollpositie blijft staan.
4. Tijdens het verzoek: knop `disabled`, tekst "Bezig…", `aria-busy="true"` op het doelblok.
5. Na vervangen worden nieuwe `data-snel`-formulieren in het vervangen blok vanzelf opgepikt
   (event delegation op `document`, geen herbinding nodig).

**Foutafhandeling:** netwerkfout of een foutstatus (419, 403, 500 …) → het formulier wordt alsnog
gewoon verstuurd (`form.submit()`). Een geslaagd antwoord zonder `#<id>` (de actie is dan al
verwerkt) → de browser gaat naar die pagina (`location.assign`) in plaats van opnieuw te versturen,
zodat een actie nooit dubbel gebeurt. Validatie-
fouten komen via de gewone redirect-met-fouten mee in het ververste blok.

**Toegankelijkheid:** `#meldingen` heeft `aria-live="polite"`, zodat de melding wordt voorgelezen.
Wie met het toetsenbord werkte, houdt zijn plek: de focus gaat terug naar dezelfde knop in het
nieuwe blok, of naar het blok zelf als die knop er niet meer is.

| Handeling | Formulier in | Doelblok |
|---|---|---|
| Inschrijven voor een reis | `traveler/register-trip` | `#reizen` |
| Activiteit kiezen | `x-activity-card` op `traveler/activities` | `#activiteiten` |
| Keuze annuleren | `traveler/my-choices` | `#keuzes` |
| Checklist afvinken / punt toevoegen | `traveler/my-choices` | `#checklist` |
| Goedkeuren / afwijzen | `coordinator/registrations/index` | `#aanvragen` |

Bij goedkeuren/afwijzen schuift de rij weg (korte animatie) voordat het blok vervangen wordt.

## 3. Testen

- Controllers veranderen niet; de bestaande 68 tests blijven groen. Tests die op oude markup
  controleren worden bijgewerkt, niet versoepeld.
- Nieuwe feature-test `SnelleHandelingenTest`: per handeling controleren dat de pagina een formulier
  met `data-snel="<id>"` bevat én een element met `id="<id>"`, en dat de pagina waarnaar de
  controller na de actie doorstuurt dat element ook bevat. Dat is de naad die stil kan breken.
- `x-activity-card`: test op de vier statussen (tekst in de band), inclusief de grens van 75 %.
- JavaScript wordt handmatig in de browser gecontroleerd: alle vijf handelingen, een validatiefout
  op de checklist, en het terugvallen op gewoon versturen bij een fout.
- `npm run build` na afloop.

## 4. Documentatie

README en `HANDOVER.md`: punt A als *bewust zo gelaten* (2 s wachttijd geaccepteerd); restpunten
knopvariant, `select`, lettertype en focusring naar *opgelost*.
