<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions\RelationManagers;

use App\Domain\Auctions\Models\Auction;
use App\Domain\Auctions\Models\AuctionLot;
use App\Domain\Catalog\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/** The pieces offered in an auction, numbered. A lot may point to a catalogue piece (its photo is reused). */
class LotsRelationManager extends RelationManager
{
    protected static string $relationship = 'lots';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.auctions.lots');
    }

    protected static function getModelLabel(): ?string
    {
        return __('admin.auctions.lot');
    }

    public function form(Schema $schema): Schema
    {
        $auctionId = $this->getOwnerRecord()->getKey();

        return $schema->columns(2)->components([
            TextInput::make('lot_number')->label(__('admin.auctions.lot_number'))->integer()->minValue(1)->maxValue(9999)->required()
                ->default(fn () => (int) AuctionLot::query()->where('auction_id', $auctionId)->max('lot_number') + 1)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('auction_id', $auctionId)),
            Select::make('product_id')->label(__('admin.auctions.product'))->helperText(__('admin.auctions.product_hint'))
                ->relationship('product', 'name_ar')->searchable(['name_ar', 'name_en', 'sku'])->preload(false)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                    $product = $state ? Product::query()->find($state) : null;
                    if ($product === null) {
                        return;
                    }
                    foreach (['ar', 'en'] as $locale) {
                        if (blank($get("title_{$locale}"))) {
                            $set("title_{$locale}", $product->getAttribute("name_{$locale}"));
                        }
                    }
                }),
            TextInput::make('title_ar')->label(__('admin.fields.title_ar'))->required()->maxLength(190),
            TextInput::make('title_en')->label(__('admin.fields.title_en'))->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('description_ar')->label(__('admin.auctions.description_ar'))->rows(3)->maxLength(3000),
            Textarea::make('description_en')->label(__('admin.auctions.description_en'))->rows(3)->maxLength(3000)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('starting_price')->label(__('admin.auctions.starting_price'))
                ->numeric()->minValue(0)->maxValue(99999999)->required()->suffix(__('ui.currency')),
            TextInput::make('estimate_low')->label(__('admin.auctions.estimate_low'))
                ->numeric()->minValue(0)->maxValue(99999999)->suffix(__('ui.currency'))->requiredWith('estimate_high'),
            TextInput::make('estimate_high')->label(__('admin.auctions.estimate_high'))
                ->numeric()->minValue(0)->maxValue(99999999)->suffix(__('ui.currency'))->requiredWith('estimate_low')->gte('estimate_low'),
            SpatieMediaLibraryFileUpload::make('gallery')->label(__('admin.auctions.lot_photo'))->collection(AuctionLot::MEDIA_GALLERY)
                ->image()->multiple()->reorderable()->maxFiles(6)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)
                ->helperText(__('admin.auctions.lot_photo_hint'))->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('lot_number')
            ->emptyStateHeading(__('admin.auctions.no_lots'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('gallery')->label('')->collection(AuctionLot::MEDIA_GALLERY)->conversion('card')->imageHeight(40)->limit(1),
                TextColumn::make('lot_number')->label(__('admin.auctions.lot_number'))->sortable(),
                TextColumn::make('title_ar')->label(__('admin.fields.title'))->searchable(['title_ar', 'title_en'])->wrap(),
                TextColumn::make('product.sku')->label(__('admin.auctions.product'))->placeholder('—'),
                TextColumn::make('starting_price')->label(__('admin.auctions.starting_price'))->numeric(2)->suffix(' '.__('ui.currency')),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
