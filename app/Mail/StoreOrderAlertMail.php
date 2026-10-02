<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Internal alert to the store's mailbox about a new confirmed order or reservation. */
final class StoreOrderAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly OrderNotice $notice,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("orders.alert.{$this->notice->value}", ['number' => $this->order->number]));
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items', 'pickupBranch']);

        return new Content(view: 'mail.store-alert', with: ['order' => $this->order, 'notice' => $this->notice]);
    }
}
