<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinderRequests\Pages;

use App\Filament\Resources\FinderRequests\FinderRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListFinderRequests extends ListRecords
{
    protected static string $resource = FinderRequestResource::class;
}
