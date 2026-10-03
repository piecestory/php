<?php

declare(strict_types=1);

namespace App\Filament\Resources\Origins;

use App\Domain\Catalog\Models\Origin;
use App\Filament\Resources\Origins\Pages\ManageOrigins;
use App\Filament\Support\CatalogAttributeResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class OriginResource extends CatalogAttributeResource
{
    protected static ?string $model = Origin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('admin.attributes.origins.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.attributes.origins.plural');
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrigins::route('/')];
    }
}
