# TripCrew online zetten (Railway)

Een Cloudflare Worker kan geen PHP en geen MySQL draaien. Daarom draait TripCrew online op
[Railway](https://railway.com): Railway bouwt de `Dockerfile` in de hoofdmap van de repo en
zet er een MySQL-database naast. Je computer hoeft dan niet meer aan te staan.

Wat er al in de repo staat:

- `Dockerfile`: bouwt de frontend (Vite) en zet Laravel in Apache met PHP 8.3.
- `docker/start.sh`: luistert op de poort die Railway geeft, draait `php artisan migrate --force`
  (alleen nieuwe migraties, bestaande data blijft staan) en cachet config, routes en views.
- Mailer `brevo`: Railway blokkeert uitgaande SMTP (poort 25, 465 en 587) op de gratis en
  Hobby-plannen, dus Gmail-SMTP werkt daar niet. Online gaat de mail via de HTTPS-API van
  Brevo. Lokaal blijft het gewoon Gmail-SMTP.

## 1. Brevo (voor de mail)

1. Maak een gratis account op [brevo.com](https://www.brevo.com).
2. *Senders, Domains & Dedicated IPs* → *Senders* → voeg je Gmail-adres toe en bevestig het
   via de mail die je krijgt.
3. *SMTP & API* → *API keys* → maak een sleutel. Die heb je bij stap 2.4 nodig.

## 2. Railway

1. Log in op railway.com met je GitHub-account.
2. *New Project* → *Deploy from GitHub repo* → kies de TripCrew-repo. Railway ziet de
   `Dockerfile` en gaat bouwen. De eerste keer mislukt de start nog: er is nog geen database.
3. In hetzelfde project: *+ Create* → *Database* → *MySQL*.
4. Klik op de TripCrew-service → *Variables* → *Raw Editor* en plak dit (vul de `<...>` in):

   ```
   APP_NAME=TripCrew
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<zie hieronder>
   APP_URL=https://<je-railway-adres>
   APP_LOCALE=nl
   APP_FALLBACK_LOCALE=en
   LOG_CHANNEL=stderr
   LOG_LEVEL=warning

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database

   MAIL_MAILER=brevo
   BREVO_API_KEY=<je Brevo-API-sleutel>
   MAIL_FROM_ADDRESS=<het Gmail-adres dat je bij Brevo bevestigde>
   MAIL_FROM_NAME=TripCrew
   ```

   - `APP_KEY`: maak een nieuwe met `php artisan key:generate --show` en plak de uitkomst
     (begint met `base64:`). Gebruik niet die uit je lokale `.env`.
   - `${{MySQL....}}` laat je precies zo staan: Railway vult het zelf in vanuit de database.
5. *Settings* → *Networking* → *Generate Domain*. Zet dat adres in `APP_URL` (met `https://`).
6. *Settings* → *Deploy* → *Healthcheck Path*: `/up`.
7. Railway deployt opnieuw. Werkt `https://<je-railway-adres>/inloggen`? Dan staat hij online.

Na elke push naar `main` bouwt Railway automatisch een nieuwe versie.

## 3. Eerste gebruikers

De database is leeg. Er is een coördinator nodig, en die maakt de seeder aan. Installeer de
[Railway CLI](https://docs.railway.com/guides/cli) en draai vanuit de projectmap:

```
railway login
railway link
railway ssh
php artisan db:seed --force
```

**Let op:** de seeder maakt demo-accounts met het wachtwoord `password`, en de site is
openbaar. Geef de coördinator meteen een eigen wachtwoord (nog steeds in `railway ssh`):

```
php artisan tinker --execute="App\Models\User::where('email', 'coordinator@tripcrew.test')->update(['password' => bcrypt('<nieuw wachtwoord>')])"
```

Doe hetzelfde voor `reiziger@tripcrew.test`, of laat de demo-accounts weg als je ze niet nodig
hebt.

## 4. Het workers.dev-adres houden (optioneel)

Wil je `tripcrew.javiprime41.workers.dev` blijven gebruiken, zet dan in `wrangler.jsonc` bij
`ORIGIN` het Railway-adres (bijv. `https://tripcrew-production.up.railway.app`) en maak
`APP_URL` op Railway gelijk aan het workers.dev-adres. Na een push bouwt Cloudflare de Worker
opnieuw. De tunnel en XAMPP heb je dan niet meer nodig.

Zonder Worker kun je gewoon het Railway-adres delen.

## Kosten

Railway en Brevo hebben een gratis instap, maar de voorwaarden veranderen weleens. Kijk op
hun prijspagina's wat er nu gratis is, zodat je niet voor een verrassing komt te staan.
