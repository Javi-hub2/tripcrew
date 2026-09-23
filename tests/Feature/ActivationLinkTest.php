<?php

namespace Tests\Feature;

use App\Mail\ActivationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * De activatielink moet kloppen zodra de site achter Cloudflare staat. Daar komt
 * het request bij Laravel binnen als http op een intern adres; dat het van buiten
 * https was, staat alleen in de X-Forwarded-headers. Vertrouwt Laravel die niet,
 * dan mailen we onze bezoekers een link naar http:// en de verkeerde host.
 */
class ActivationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_activation_link_follows_the_proxy_headers(): void
    {
        Mail::fake();

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tripcrew.nl',
        ])->post('/registreren', [
            'name' => 'Nieuwe Reiziger',
            'email' => 'achter-de-proxy@example.test',
        ])->assertSessionHas('success');

        Mail::assertSent(ActivationMail::class, function (ActivationMail $mail) {
            $url = $mail->content()->with['url'];
            $this->assertStringStartsWith('https://tripcrew.nl/activeren/', $url, "Link was: {$url}");

            return true;
        });
    }

    public function test_the_activation_link_is_plain_http_without_proxy_headers(): void
    {
        Mail::fake();

        $this->post('/registreren', [
            'name' => 'Nieuwe Reiziger',
            'email' => 'zonder-proxy@example.test',
        ])->assertSessionHas('success');

        Mail::assertSent(ActivationMail::class, function (ActivationMail $mail) {
            // Zonder proxyheaders gewoon de eigen host uit APP_URL, en dus http.
            $this->assertStringStartsWith(url('/activeren').'/', $mail->content()->with['url']);
            $this->assertStringStartsWith('http://', $mail->content()->with['url']);

            return true;
        });
    }
}
