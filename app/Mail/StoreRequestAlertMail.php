<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Shared\Contracts\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the team a new request arrived (details are read in the admin; photos stay private there). */
final class StoreRequestAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Model&ServiceRequest $serviceRequest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("requests.{$this->serviceRequest->requestKind()}.alert", ['reference' => $this->serviceRequest->requestReference()]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.request-alert', with: ['serviceRequest' => $this->serviceRequest]);
    }
}
