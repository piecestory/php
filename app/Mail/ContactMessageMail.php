<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Content\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A visitor's message forwarded to the store mailbox; replying answers the visitor directly when they left an email. */
final class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('contact.mail.subject', ['name' => $this->contactMessage->name]),
            replyTo: $this->contactMessage->email ? [new Address($this->contactMessage->email, $this->contactMessage->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-message', with: ['contactMessage' => $this->contactMessage]);
    }
}
