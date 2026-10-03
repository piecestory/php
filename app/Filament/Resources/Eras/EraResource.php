<?php

declare(strict_types=1);

namespace App\Filament\Resources\Eras;

use App\Domain\Catalog\Models\Era;
use App\Filament\Resources\Eras\Pages\ManageEras;
use App\Filament\Support\CatalogAttributeResource;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class EraResource extends CatalogAttributeResource
{
    protected static ?string $model = Era::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('admin.attributes.eras.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.attributes.eras.plural');
    }

    protected static function extraFields(): array
    {
        return [
            TextInput::make('year_from')->label(__('admin.attributes.eras.year_from'))->integer()->minValue(0)->maxValue(2100),
            TextInput::make('year_to')->label(__('admin.attributes.eras.year_to'))->integer()->minValue(0)->maxValue(2100)->gte('year_from'),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ManageEras::route('/')];
    }
}
