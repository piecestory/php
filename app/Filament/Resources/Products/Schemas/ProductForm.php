<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Enums\ProductAvailability;
use App\Domain\Catalog\Enums\ProductCondition;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\PublicationStatus;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Fields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make(__('admin.products.tabs.basics'))->columns(2)->schema([
                    ...Fields::bilingualName(),
                    TextInput::make('sku')->label(__('admin.products.sku'))->required()->maxLength(40)
                        ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('category_id')->label(__('admin.products.category'))
                        ->relationship('category', 'name_ar')->searchable()->preload()->required(),
                    Select::make('collections')->label(__('admin.products.collections'))
                        ->relationship('collections', 'name_ar')->multiple()->preload()->columnSpanFull(),
                ]),

                Tab::make(__('admin.products.tabs.story'))->schema([
                    Textarea::make('description_ar')->label(__('admin.fields.description_ar'))->rows(5),
                    Textarea::make('description_en')->label(__('admin.fields.description_en'))->rows(5)->extraInputAttributes(['dir' => 'ltr']),
                    Textarea::make('story_ar')->label(__('admin.products.story_ar'))->helperText(__('admin.products.story_hint'))->rows(6),
                    Textarea::make('story_en')->label(__('admin.products.story_en'))->rows(6)->extraInputAttributes(['dir' => 'ltr']),
                ]),

                Tab::make(__('admin.products.tabs.pricing'))->columns(2)->schema([
                    TextInput::make('price')->label(__('admin.products.price'))->helperText(__('admin.products.vat_inclusive'))
                        ->numeric()->minValue(0)->maxValue(99999999)->required()->suffix(__('ui.currency')),
                    TextInput::make('sale_price')->label(__('admin.products.sale_price'))->numeric()->minValue(0)
                        ->lt('price')->suffix(__('ui.currency')),
                    DateTimePicker::make('sale_starts_at')->label(__('admin.products.sale_starts_at'))->seconds(false),
                    DateTimePicker::make('sale_ends_at')->label(__('admin.products.sale_ends_at'))->seconds(false)->after('sale_starts_at'),
                    Select::make('availability')->label(__('admin.products.availability'))
                        ->options(EnumLabels::options(ProductAvailability::class))->default(ProductAvailability::Available->value)->required(),
                    // Stock changes after creation go through "Adjust stock" so every change is in the movement log.
                    TextInput::make('stock_quantity')->label(__('admin.products.stock'))->integer()->minValue(0)->maxValue(999)
                        ->default(1)->required()
                        ->disabled(fn (?Product $record) => $record !== null)->dehydrated(fn (?Product $record) => $record === null)
                        ->helperText(fn (?Product $record) => $record ? __('admin.products.stock_hint') : null),
                ]),

                Tab::make(__('admin.products.tabs.details'))->columns(2)->schema([
                    Select::make('origin_id')->label(__('admin.products.origin'))->relationship('origin', 'name_ar')->searchable()->preload(),
                    Select::make('era_id')->label(__('admin.products.era'))->relationship('era', 'name_ar')->searchable()->preload(),
                    Select::make('materials')->label(__('admin.products.materials'))->relationship('materials', 'name_ar')->multiple()->preload(),
                    Select::make('condition')->label(__('admin.products.condition'))->options(EnumLabels::options(ProductCondition::class)),
                    Grid::make(4)->columnSpanFull()->schema([
                        TextInput::make('width_cm')->label(__('admin.products.width'))->numeric()->minValue(0)->suffix(__('product.spec.cm')),
                        TextInput::make('height_cm')->label(__('admin.products.height'))->numeric()->minValue(0)->suffix(__('product.spec.cm')),
                        TextInput::make('depth_cm')->label(__('admin.products.depth'))->numeric()->minValue(0)->suffix(__('product.spec.cm')),
                        TextInput::make('weight_kg')->label(__('admin.products.weight'))->numeric()->minValue(0)->suffix(__('product.spec.kg')),
                    ]),
                    Toggle::make('is_rare')->label(__('admin.products.is_rare')),
                    Toggle::make('is_featured')->label(__('admin.products.is_featured')),
                ]),

                Tab::make(__('admin.products.tabs.images'))->schema([
                    SpatieMediaLibraryFileUpload::make('gallery')->label(__('admin.products.gallery'))
                        ->collection(Product::MEDIA_GALLERY)
                        ->multiple()->reorderable()->appendFiles()->maxFiles(20)
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)
                        ->panelLayout('grid')
                        ->helperText(__('admin.products.gallery_hint')),
                ]),

                Tab::make(__('admin.products.tabs.publishing'))->columns(2)->schema([
                    Select::make('status')->label(__('admin.fields.status'))
                        ->options(EnumLabels::options(PublicationStatus::class))->default(PublicationStatus::Draft->value)->required()->live(),
                    DateTimePicker::make('published_at')->label(__('admin.fields.published_at'))->seconds(false)
                        ->helperText(__('admin.fields.published_at_hint'))
                        ->visible(fn (Get $get) => $get('status') === PublicationStatus::Published->value || $get('status') === PublicationStatus::Published),
                    ...Fields::seo(),
                ]),
            ]),
        ]);
    }
}
