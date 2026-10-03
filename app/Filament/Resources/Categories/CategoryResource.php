<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\Fields;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'name_ar';

    /** Admin URLs use the id: storefront slugs can be edited here and must not move the admin page. */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?int $navigationSort = 2;

    protected static function permission(): Permission
    {
        return Permission::ManageCatalog;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['forceDelete', 'forceDeleteAny', 'deleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.categories.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            ...Fields::bilingualName(),
            Select::make('parent_id')->label(__('admin.categories.parent'))
                ->relationship('parent', 'name_ar', fn (Builder $query, ?Category $record) => $query->whereNull('parent_id')->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                ->placeholder(__('admin.categories.top_level')),
            TextInput::make('sort_order')->label(__('admin.fields.sort_order'))->integer()->minValue(0)->default(0),
            Textarea::make('description_ar')->label(__('admin.fields.description_ar'))->rows(3),
            Textarea::make('description_en')->label(__('admin.fields.description_en'))->rows(3)->extraInputAttributes(['dir' => 'ltr']),
            SpatieMediaLibraryFileUpload::make('image')->label(__('admin.fields.image'))->collection(Category::MEDIA_IMAGE)
                ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)->columnSpanFull(),
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
                SpatieMediaLibraryImageColumn::make('image')->label('')->collection(Category::MEDIA_IMAGE)->conversion('tile')->square()->imageSize(48),
                TextColumn::make('name_ar')->label(__('admin.fields.name'))->searchable(['name_ar', 'name_en'])
                    ->description(fn (Category $record) => $record->parent?->name_ar),
                TextColumn::make('products_count')->label(__('admin.products.plural'))->counts('products'),
                ToggleColumn::make('is_active')->label(__('admin.fields.is_active')),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                EditAction::make(),
                // A category holding pieces cannot be removed: move the pieces first.
                DeleteAction::make()->label(__('admin.actions.archive'))->hidden(fn (Category $record) => $record->products()->exists()),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent');
    }

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
