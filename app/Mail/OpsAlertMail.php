<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Plain-text alert to the team (sent immediately, not queued: the queue may be what is failing). */
final class OpsAlertMail extends Mailable
{
    public function __construct(public readonly string $alertSubject, public readonly string $details) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.config('app.name').'] '.$this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.ops-alert', with: ['details' => $this->details]);
    }
}
