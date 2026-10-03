<?php

declare(strict_types=1);

namespace App\Domain\Consignment\Actions;

use App\Domain\Consignment\Enums\ConsignmentStatus;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Shared\Actions\NotifyRequester;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Staff review of a piece offered for sale: mark under review, then approve or decline. The decision
 * and its note (next steps, or the reason) are sent to the customer.
 */
final class ReviewConsignment
{
    public function __construct(private readonly NotifyRequester $notify) {}

    public function startReview(ConsignmentRequest $request, int $staffId): void
    {
        $this->move($request, ConsignmentStatus::Reviewing, $staffId, null);
    }

    public function decide(ConsignmentRequest $request, bool $approve, string $noteToCustomer, int $staffId): void
    {
        $to = $approve ? ConsignmentStatus::Approved : ConsignmentStatus::Rejected;
        $this->move($request, $to, $staffId, $noteToCustomer);

        $this->notify->handle($request, $approve ? 'approved' : 'rejected', $noteToCustomer);
    }

    private function move(ConsignmentRequest $request, ConsignmentStatus $to, int $staffId, ?string $note): void
    {
        $from = $request->status;
        if (! $from->canTransitionTo($to)) {
            throw new LogicException("Consignment {$request->reference} cannot move from {$from->value} to {$to->value}.");
        }

        DB::transaction(function () use ($request, $from, $to, $staffId, $note): void {
            $request->forceFill([
                'status' => $to,
                'reviewed_by' => $staffId,
                'decision_note' => $note ?? $request->decision_note,
                'decided_at' => in_array($to, [ConsignmentStatus::Approved, ConsignmentStatus::Rejected], true) ? now() : null,
            ])->save();
            $request->recordStatusChange($from, $to, $staffId, $note);
        });
    }
}
