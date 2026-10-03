<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions;

use App\Domain\Auctions\Enums\AuctionPhase;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Auctions\Models\Auction;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\Auctions\Pages\CreateAuction;
use App\Filament\Resources\Auctions\Pages\EditAuction;
use App\Filament\Resources\Auctions\Pages\ListAuctions;
use App\Filament\Resources\Auctions\RelationManagers\InterestsRelationManager;
use App\Filament\Resources\Auctions\RelationManagers\LotsRelationManager;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\Fields;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Auction showcase (v1): staff publish an auction with its lots and follow who registered interest.
 * Staff choose draft / published / cancelled; upcoming, live and ended follow from the dates.
 */
class AuctionResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Auction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $recordTitleAttribute = 'title_ar';

    /** Admin URLs use the id: storefront slugs can be edited here and must not move the admin page. */
    protected static ?string $recordRouteKeyName = 'id';

    protected static ?int $navigationSort = 6;

    protected static function permission(): Permission
    {
        return Permission::ManageAuctions;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return [];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.sales');
    }

    public static function getModelLabel(): string
    {
        return __('admin.auctions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.auctions.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.auctions.details'))->columns(2)->columnSpanFull()->schema([
                ...Fields::bilingualName(field: 'title'),
                Textarea::make('description_ar')->label(__('admin.auctions.description_ar'))->rows(4)->maxLength(5000),
                Textarea::make('description_en')->label(__('admin.auctions.description_en'))->rows(4)->maxLength(5000)
                    ->extraInputAttributes(['dir' => 'ltr']),
            ]),
            Section::make(__('admin.auctions.schedule'))->columns(3)->columnSpanFull()->schema([
                DateTimePicker::make('starts_at')->label(__('admin.auctions.starts_at'))->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label(__('admin.auctions.ends_at'))->seconds(false)->required()->after('starts_at'),
                Select::make('status')->label(__('admin.fields.status'))
                    ->options(EnumLabels::options(AuctionStatus::class, AuctionStatus::editable()))
                    ->default(AuctionStatus::Draft->value)->required()
                    ->helperText(__('admin.auctions.status_hint')),
            ]),
            Section::make(__('admin.auctions.cover'))->columnSpanFull()->schema([
                SpatieMediaLibraryFileUpload::make('cover')->hiddenLabel()->collection(Auction::MEDIA_COVER)
                    ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(15360)
                    ->helperText(__('admin.auctions.cover_hint')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->label('')->collection(Auction::MEDIA_COVER)->conversion('w800')->imageHeight(40),
                TextColumn::make('title_ar')->label(__('admin.fields.title'))->searchable(['title_ar', 'title_en'])->wrap(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (AuctionStatus $state) => EnumLabels::of($state))
                    ->color(fn (AuctionStatus $state) => match (true) {
                        $state->isPublic() => 'success',
                        $state === AuctionStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('phase')->label(__('admin.auctions.phase'))->badge()
                    ->state(fn (Auction $record) => $record->status->isPublic() ? $record->phase()->label() : '—')
                    ->color(fn (Auction $record) => $record->status->isPublic() && $record->phase() === AuctionPhase::Live ? 'warning' : 'gray'),
                TextColumn::make('starts_at')->label(__('admin.auctions.starts_at'))->dateTime('j M Y، g:i a')->sortable(),
                TextColumn::make('ends_at')->label(__('admin.auctions.ends_at'))->dateTime('j M Y، g:i a')->sortable(),
                TextColumn::make('lots_count')->label(__('admin.auctions.lots'))->counts('lots'),
                TextColumn::make('interests_count')->label(__('admin.auctions.interests'))->counts('interests'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))
                    ->options(EnumLabels::options(AuctionStatus::class, AuctionStatus::editable())),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            LotsRelationManager::class,
            InterestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuctions::route('/'),
            'create' => CreateAuction::route('/create'),
            'edit' => EditAuction::route('/{record}/edit'),
        ];
    }
}
