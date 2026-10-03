<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions\Pages;

use App\Domain\Auctions\Models\Auction;
use App\Filament\Resources\Auctions\AuctionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAuction extends EditRecord
{
    protected static string $resource = AuctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('admin.actions.view_on_site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (Auction $record) => localized_route('auctions.show', $record), shouldOpenInNewTab: true)
                ->visible(fn (Auction $record) => $record->status->isPublic()),
            // Deleting also removes the lots and the interest list; cancelling keeps them.
            DeleteAction::make()->modalDescription(__('admin.auctions.delete_hint')),
        ];
    }
}
