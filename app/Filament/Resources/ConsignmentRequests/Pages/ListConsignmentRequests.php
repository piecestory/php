<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsignmentRequests\Pages;

use App\Filament\Resources\ConsignmentRequests\ConsignmentRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListConsignmentRequests extends ListRecords
{
    protected static string $resource = ConsignmentRequestResource::class;
}
