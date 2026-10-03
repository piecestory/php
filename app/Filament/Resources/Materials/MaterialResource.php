<?php

declare(strict_types=1);

namespace App\Filament\Resources\Materials;

use App\Domain\Catalog\Models\Material;
use App\Filament\Resources\Materials\Pages\ManageMaterials;
use App\Filament\Support\CatalogAttributeResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class MaterialResource extends CatalogAttributeResource
{
    protected static ?string $model = Material::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('admin.attributes.materials.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.attributes.materials.plural');
    }

    public static function getPages(): array
    {
        return ['index' => ManageMaterials::route('/')];
    }
}
