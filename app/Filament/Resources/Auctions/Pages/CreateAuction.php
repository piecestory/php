<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions\Pages;

use App\Filament\Resources\Auctions\AuctionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuction extends CreateRecord
{
    protected static string $resource = AuctionResource::class;

    /** Lots are added on the edit page, once the auction exists. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
