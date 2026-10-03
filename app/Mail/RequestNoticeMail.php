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

/** Message to a customer about their Personal Finder or "sell with us" request. */
final class RequestNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Model&ServiceRequest $serviceRequest,
        public readonly string $event,
        public readonly ?string $staffMessage = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __($this->key().'.subject', ['reference' => $this->serviceRequest->requestReference()]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.request-notice', with: [
            'serviceRequest' => $this->serviceRequest,
            'key' => $this->key(),
            'staffMessage' => $this->staffMessage,
        ]);
    }

    private function key(): string
    {
        return "requests.{$this->serviceRequest->requestKind()}.{$this->event}";
    }
}
