<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions\RelationManagers;

use App\Support\Phone\SaudiMobile;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Who registered interest (and in which lot), newest first, so the team can contact them. Read-only. */
class InterestsRelationManager extends RelationManager
{
    protected static string $relationship = 'interests';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.auctions.interests');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('lot'))
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('admin.auctions.no_interests'))
            ->columns([
                TextColumn::make('created_at')->label(__('admin.fields.date'))->dateTime('j M Y، g:i a'),
                TextColumn::make('name')->label(__('admin.auctions.interest_name'))->searchable(),
                TextColumn::make('phone')->label(__('admin.auctions.interest_phone'))->searchable()
                    ->formatStateUsing(fn (string $state) => SaudiMobile::local($state))
                    ->extraAttributes(['dir' => 'ltr'])->copyable(),
                TextColumn::make('email')->label(__('admin.auctions.interest_email'))->placeholder('—')->copyable(),
                TextColumn::make('lot.lot_number')->label(__('admin.auctions.interest_lot'))
                    ->formatStateUsing(fn ($state, $record) => $record->lot ? "#{$state} — {$record->lot->title_ar}" : '')
                    ->placeholder(__('admin.auctions.whole_auction')),
            ]);
    }
}
