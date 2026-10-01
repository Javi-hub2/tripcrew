<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

/**
 * Online (Railway) kan de app geen SMTP gebruiken, dus daar staat MAIL_MAILER=brevo.
 * Bestaat die mailer niet of mist de sleutel, dan faalt pas de eerste echte registratie.
 */
class BrevoMailerTest extends TestCase
{
    public function test_the_brevo_mailer_sends_through_the_https_api(): void
    {
        config(['services.brevo.key' => 'test-sleutel']);

        $transport = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(BrevoApiTransport::class, $transport);
        $this->assertSame('brevo+api://api.brevo.com', (string) $transport);
    }
}
