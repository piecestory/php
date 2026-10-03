<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Filament\Resources\Pages\ManageRecords;

class ManageShippingMethods extends ManageRecords
{
    protected static string $resource = ShippingMethodResource::class;
}
