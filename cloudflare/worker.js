// Doorgeefluik: een Worker kan geen PHP draaien, dus hij stuurt elk verzoek door naar de
// Cloudflare Tunnel die naar de Laravel-server wijst. Door X-Forwarded-Host bouwt Laravel alle
// links en redirects met het workers.dev-adres (zie trustProxies in bootstrap/app.php).
export default {
    async fetch(request, env) {
        if (!env.ORIGIN) {
            return new Response('ORIGIN is niet ingesteld (adres van de tunnel).', { status: 500 });
        }

        const url = new URL(request.url);
        const target = new URL(url.pathname + url.search, env.ORIGIN);

        const headers = new Headers(request.headers);
        headers.set('X-Forwarded-Host', url.host);
        headers.set('X-Forwarded-Proto', 'https');
        // Het IP van de bezoeker, anders delen alle bezoekers één IP en raakt de rate limiter
        // (5 pogingen per minuut) voor iedereen tegelijk op.
        headers.set('X-Forwarded-For', request.headers.get('CF-Connecting-IP') ?? '');

        try {
            return await fetch(target, {
                method: request.method,
                headers,
                body: ['GET', 'HEAD'].includes(request.method) ? undefined : request.body,
                redirect: 'manual',
            });
        } catch {
            return new Response('TripCrew is nu niet bereikbaar. Staat de server met de tunnel aan?', {
                status: 502,
                headers: { 'Content-Type': 'text/plain; charset=utf-8' },
            });
        }
    },
};
