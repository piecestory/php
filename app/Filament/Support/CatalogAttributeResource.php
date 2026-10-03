<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Identity\Enums\Permission;
use App\Support\Localization\Slug;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared shape of the catalogue's reference lists (origins, eras, materials): Arabic + English name,
 * a Latin slug used in filter URLs, display order. A value still used by pieces cannot be deleted.
 */
abstract class CatalogAttributeResource extends Resource
{
    use RequiresPermission;

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static function permission(): Permission
    {
        return Permission::ManageCatalog;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['deleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.catalog');
    }

    /** @return list<TextInput> extra fields after the names */
    protected static function extraFields(): array
    {
        return [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name_ar')->label(__('admin.fields.name_ar'))->required()->maxLength(100),
            TextInput::make('name_en')->label(__('admin.fields.name_en'))->required()->maxLength(100)->extraInputAttributes(['dir' => 'ltr'])
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => blank($get('slug')) && filled($state) ? $set('slug', Slug::make($state)) : null),
            TextInput::make('slug')->label(__('admin.attributes.slug'))->helperText(__('admin.attributes.slug_hint'))->required()->maxLength(100)
                ->unique(ignoreRecord: true)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('sort_order')->label(__('admin.fields.sort_order'))->integer()->minValue(0)->default(0),
            ...static::extraFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_ar')->label(__('admin.fields.name'))->searchable(['name_ar', 'name_en'])
                    ->description(fn (Model $record) => (string) $record->getAttribute('name_en')),
                TextColumn::make('products_count')->label(__('admin.products.plural'))->counts('products'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Model $record) => (int) $record->getAttribute('products_count') > 0),
            ]);
    }
}
