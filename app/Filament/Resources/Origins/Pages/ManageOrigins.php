<?php

declare(strict_types=1);

namespace App\Filament\Resources\Origins\Pages;

use App\Filament\Resources\Origins\OriginResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrigins extends ManageRecords
{
    protected static string $resource = OriginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
