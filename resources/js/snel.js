// Snelle handelingen zonder herladen.
//
// Een formulier met data-snel="<id>" wordt op de achtergrond verstuurd. De server doet
// precies hetzelfde als bij gewoon versturen (zelfde controller, zelfde redirect). Uit de
// pagina waar hij naartoe stuurt halen we alleen het blok #<id> en de meldingen (#meldingen)
// en zetten die op hun plek; de rest van de pagina en de scrollpositie blijven staan.
//
// Zonder JavaScript, of als er iets misgaat, valt alles terug op gewoon versturen.
// De koppeling formulier ↔ blok wordt bewaakt door tests/Feature/SnelleHandelingenTest.php.

const minderBeweging = window.matchMedia('(prefers-reduced-motion: reduce)');

document.addEventListener('submit', async (event) => {
    const form = event.target;

    // Een onsubmit="return confirm(...)" die op "Annuleren" uitkwam, heeft het versturen
    // al tegengehouden: dan doen wij ook niets.
    if (!(form instanceof HTMLFormElement) || !form.dataset.snel || event.defaultPrevented) {
        return;
    }

    const doel = document.getElementById(form.dataset.snel);
    if (!doel || !doel.contains(form)) {
        return;
    }

    event.preventDefault();

    const data = new FormData(form);
    const knop = event.submitter ?? form.querySelector('[type="submit"]');
    const hadFocus = form.contains(document.activeElement);

    if (knop) {
        knop.disabled = true;
        knop.textContent = 'Bezig…';
    }
    doel.setAttribute('aria-busy', 'true');

    let antwoord;
    try {
        antwoord = await fetch(form.action, {
            method: 'POST', // PATCH/DELETE gaan via het verborgen _method-veld, net als gewoon versturen
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
        });
    } catch {
        // Netwerkfout: het verzoek is niet aangekomen, dus gewoon opnieuw versturen.
        form.submit();
        return;
    }

    if (!antwoord.ok) {
        // Sessie verlopen (419), geen toegang (403), serverfout (500) …: laat de browser het
        // gewone verzoek doen, dan ziet de gebruiker de normale foutpagina of melding.
        form.submit();
        return;
    }

    const pagina = new DOMParser().parseFromString(await antwoord.text(), 'text/html');
    const nieuw = pagina.getElementById(doel.id);

    if (!nieuw) {
        // De actie is verwerkt, maar de server stuurde ons naar een pagina zonder dit blok
        // (bijv. het inlogscherm). Niet opnieuw versturen, gewoon daarheen gaan.
        window.location.assign(antwoord.url);
        return;
    }

    await laatRijVerdwijnen(form);

    doel.replaceWith(nieuw);

    const meldingen = pagina.getElementById('meldingen');
    if (meldingen) {
        document.getElementById('meldingen')?.replaceWith(meldingen);
    }

    if (hadFocus) {
        zetFocusTerug(nieuw, form.action);
    }
});

// Bij goedkeuren/afwijzen schuift de rij eerst weg (zie .snel-weg in app.css).
function laatRijVerdwijnen(form) {
    const rij = form.closest('[data-snel-rij]');
    if (!rij || minderBeweging.matches) {
        return Promise.resolve();
    }
    rij.classList.add('snel-weg');

    return new Promise((klaar) => setTimeout(klaar, 200));
}

// Toetsenbordgebruikers houden hun plek: focus terug op dezelfde knop in het nieuwe blok,
// of op het blok zelf als die knop er niet meer is (bijv. na goedkeuren).
function zetFocusTerug(blok, action) {
    const form = [...blok.querySelectorAll('form')].find((f) => f.action === action);
    const knop = form?.querySelector('button:not([disabled])');
    (knop ?? blok).focus({ preventScroll: true });
}
