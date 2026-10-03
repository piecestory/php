<?php

declare(strict_types=1);

namespace App\Domain\PersonalFinder\Actions;

use App\Domain\PersonalFinder\Enums\FinderRequestStatus;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Domain\Shared\Actions\NotifyRequester;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Moves a Personal Finder request to its next stage and records it. When an offer is sent, the
 * staff member's message (what was found, price, how to view it) goes to the customer.
 */
final class TransitionFinderRequest
{
    public function __construct(private readonly NotifyRequester $notify) {}

    public function handle(FinderRequest $request, FinderRequestStatus $to, int $staffId, ?string $customerMessage = null): void
    {
        $from = $request->status;
        if (! $from->canTransitionTo($to)) {
            throw new LogicException("Finder request {$request->reference} cannot move from {$from->value} to {$to->value}.");
        }

        DB::transaction(function () use ($request, $from, $to, $staffId, $customerMessage): void {
            $request->forceFill(['status' => $to, 'assigned_to' => $request->assigned_to ?? $staffId])->save();
            $request->recordStatusChange($from, $to, $staffId, $customerMessage);
        });

        if ($to === FinderRequestStatus::OfferSent) {
            $this->notify->handle($request, 'offer_sent', $customerMessage);
        }
    }
}
