<?php

declare(strict_types=1);

namespace App\Domain\Auctions\Actions;

use App\Domain\Auctions\Exceptions\AuctionClosed;
use App\Domain\Auctions\Models\Auction;
use App\Domain\Auctions\Models\AuctionInterest;
use App\Domain\Auctions\Models\AuctionLot;
use App\Domain\Settings\StoreSettings;
use App\Jobs\SendSms;
use App\Mail\AuctionInterestAlertMail;
use Illuminate\Support\Facades\Mail;

/**
 * Records that a visitor wants to take part in an auction (optionally a specific lot), so the team can
 * contact them before it opens. One registration per phone, auction and lot: repeats are not stored or
 * re-notified. Confirms by SMS and alerts the team by email.
 */
final class RegisterAuctionInterest
{
    public function __construct(private readonly StoreSettings $settings) {}

    /** @param  array{name: string, phone: string, email: ?string, user_id: ?int}  $contact */
    public function handle(Auction $auction, ?AuctionLot $lot, array $contact): AuctionInterest
    {
        if (! $auction->status->isPublic() || ! $auction->phase()->acceptsInterest()) {
            throw new AuctionClosed;
        }

        if ($lot !== null && $lot->auction_id !== $auction->id) {
            throw new AuctionClosed;
        }

        $existing = AuctionInterest::query()
            ->where('auction_id', $auction->id)
            ->where('auction_lot_id', $lot?->id)
            ->where('phone', $contact['phone'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $interest = AuctionInterest::query()->create([...$contact, 'auction_id' => $auction->id, 'auction_lot_id' => $lot?->id]);

        $title = (string) $auction->translate('title');
        SendSms::dispatch($contact['phone'], __('auctions.interest.sms', ['auction' => $title]));

        $storeEmail = $this->settings->get('store.email');
        if ($storeEmail !== null) {
            Mail::to($storeEmail)->locale('ar')->queue(new AuctionInterestAlertMail($interest));
        }

        return $interest;
    }
}
