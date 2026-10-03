<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Auctions\Models\AuctionInterest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the team someone registered interest in an auction (full list in the admin). */
final class AuctionInterestAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly AuctionInterest $interest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auctions.interest.alert', ['auction' => $this->interest->auction->translate('title')]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.auction-interest-alert', with: ['interest' => $this->interest]);
    }
}
