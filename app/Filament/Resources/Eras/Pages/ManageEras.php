<?php

declare(strict_types=1);

namespace App\Filament\Resources\Eras\Pages;

use App\Filament\Resources\Eras\EraResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEras extends ManageRecords
{
    protected static string $resource = EraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
