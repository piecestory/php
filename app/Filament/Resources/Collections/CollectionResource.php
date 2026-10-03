<?php

declare(strict_types=1);

namespace App\Filament\Resources\Collections;

use App\Domain\Catalog\Models\Collection;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Collections\Pages\ManageCollections;
use App\Filament\Support\Fields;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Curated groups of pieces ("Ottoman brass", "Ramadan gifts"). Pieces are added to collections from the piece form. */
class CollectionResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name_ar';

    /** Admin URLs use the id: storefront slugs can be edited here and must not move the admin page. */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?int $navigationSort = 3;

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

    public static function getModelLabel(): string
    {
        return __('admin.collections.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.collections.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            ...Fields::bilingualName(),
            Textarea::make('description_ar')->label(__('admin.fields.description_ar'))->rows(3),
            Textarea::make('description_en')->label(__('admin.fields.description_en'))->rows(3)->extraInputAttributes(['dir' => 'ltr']),
            SpatieMediaLibraryFileUpload::make('cover')->label(__('admin.collections.cover'))->collection(Collection::MEDIA_COVER)
                ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)->columnSpanFull(),
            TextInput::make('sort_order')->label(__('admin.fields.sort_order'))->integer()->minValue(0)->default(0),
            Toggle::make('is_active')->label(__('admin.fields.is_active'))->default(true),
            Section::make(__('admin.products.tabs.publishing'))->collapsed()->columns(2)->columnSpanFull()->schema(Fields::seo()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->label('')->collection(Collection::MEDIA_COVER)->conversion('w800')->square()->imageSize(48),
                TextColumn::make('name_ar')->label(__('admin.fields.name'))->searchable(['name_ar', 'name_en']),
                TextColumn::make('products_count')->label(__('admin.products.plural'))->counts('products'),
                ToggleColumn::make('is_active')->label(__('admin.fields.is_active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCollections::route('/')];
    }
}
