<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinderRequests;

use App\Domain\Identity\Enums\Permission;
use App\Domain\PersonalFinder\Enums\FinderRequestStatus;
use App\Domain\PersonalFinder\Models\FinderRequest;
use App\Filament\Resources\FinderRequests\Pages\ListFinderRequests;
use App\Filament\Resources\FinderRequests\Pages\ViewFinderRequest;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use App\Support\Money\Money;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Personal Finder requests: staff move them through the search and send offers to the customer. */
class FinderRequestResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = FinderRequest::class;

    protected static ?string $slug = 'finder-requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 3;

    protected static function permission(): Permission
    {
        return Permission::ManageFinderRequests;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'update', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.requests.finder_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.requests.finder_plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FinderRequest::query()->where('status', FinderRequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (?string $state) => $state === null ? '—' : Money::amount($state).' '.Money::currency();

        return $schema->columns(3)->components([
            Section::make(__('admin.requests.request'))->columnSpan(2)->columns(2)->schema([
                TextEntry::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (FinderRequestStatus $state) => $state->label()),
                TextEntry::make('category.name_ar')->label(__('requests.fields.category_id'))->placeholder(__('requests.any_category')),
                TextEntry::make('description')->label(__('requests.fields.description'))->columnSpanFull()->extraAttributes(['class' => 'whitespace-pre-line']),
                TextEntry::make('budget_min')->label(__('requests.fields.budget_min'))->formatStateUsing($money)->placeholder('—'),
                TextEntry::make('budget_max')->label(__('requests.fields.budget_max'))->formatStateUsing($money)->placeholder('—'),
                TextEntry::make('preferences')->label(__('requests.fields.preferences'))->placeholder('—')->columnSpanFull(),
                ViewEntry::make('photos')->label(__('requests.fields.photos'))->view('filament.request-photos')
                    ->viewData(['collection' => FinderRequest::MEDIA_REFERENCES])->columnSpanFull(),
            ]),
            Section::make(__('requests.contact'))->columnSpan(1)->schema([
                TextEntry::make('reference')->label(__('requests.reference'))->copyable(),
                TextEntry::make('name')->label(__('requests.fields.name')),
                TextEntry::make('phone')->label(__('requests.fields.phone'))
                    ->formatStateUsing(fn (string $state) => SaudiMobile::local($state))->url(fn (FinderRequest $record) => 'tel:'.$record->phone),
                TextEntry::make('email')->label(__('requests.fields.email'))->placeholder('—'),
                TextEntry::make('assignee.name')->label(__('admin.requests.assignee'))->placeholder('—'),
                TextEntry::make('admin_notes')->label(__('admin.requests.admin_notes'))->placeholder('—')->extraAttributes(['class' => 'whitespace-pre-line']),
            ]),
            Section::make(__('admin.orders.history'))->columnSpanFull()->schema([
                RepeatableEntry::make('statusChanges')->hiddenLabel()->contained(false)->schema([
                    TextEntry::make('to_status')->hiddenLabel()->weight('medium')
                        ->formatStateUsing(fn (string $state) => FinderRequestStatus::from($state)->label())
                        ->helperText(fn ($record) => trim(($record->created_at?->translatedFormat('j M Y، g:i a') ?? '').' · '.($record->author->name ?? __('admin.stock.system')).($record->note ? ' — '.$record->note : ''), ' ·')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference')->label(__('requests.reference'))->searchable(),
                TextColumn::make('name')->label(__('requests.fields.name'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('name', 'like', "%{$search}%")->orWhere('phone', SaudiMobile::normalize($search) ?? $search)))
                    ->description(fn (FinderRequest $record) => SaudiMobile::local($record->phone)),
                TextColumn::make('description')->label(__('requests.fields.description'))->limit(70)->wrap(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (FinderRequestStatus $state) => $state->label())
                    ->color(fn (FinderRequestStatus $state) => match ($state) {
                        FinderRequestStatus::New => 'warning',
                        FinderRequestStatus::Closed, FinderRequestStatus::Cancelled => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('created_at')->label(__('admin.fields.date'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->multiple()
                    ->options(EnumLabels::options(FinderRequestStatus::class)),
            ])
            ->recordActions([ViewAction::make(), DeleteAction::make()]);
    }

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->with(['statusChanges', 'category', 'assignee', 'media']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinderRequests::route('/'),
            'view' => ViewFinderRequest::route('/{record}'),
        ];
    }
}
