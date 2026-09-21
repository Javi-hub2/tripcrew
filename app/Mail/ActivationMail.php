<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Stel je wachtwoord in voor TripCrew');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activation',
            with: [
                'name' => $this->user->name,
                'url' => route('activation.show', $this->user->activation_token),
            ],
        );
    }
}
