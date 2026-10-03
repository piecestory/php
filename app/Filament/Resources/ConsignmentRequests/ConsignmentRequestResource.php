<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsignmentRequests;

use App\Domain\Consignment\Enums\ConsignmentStatus;
use App\Domain\Consignment\Models\ConsignmentRequest;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\ConsignmentRequests\Pages\ListConsignmentRequests;
use App\Filament\Resources\ConsignmentRequests\Pages\ViewConsignmentRequest;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use App\Support\Money\Money;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
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

/** Pieces customers offer to sell through the store: review, then approve or decline with a note. */
class ConsignmentRequestResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = ConsignmentRequest::class;

    protected static ?string $slug = 'consignments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 4;

    protected static function permission(): Permission
    {
        return Permission::ManageConsignments;
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
        return __('admin.requests.consignment_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.requests.consignment_plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ConsignmentRequest::query()->where('status', ConsignmentStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('requests.consignment.piece'))->columnSpan(2)->columns(2)->schema([
                TextEntry::make('title')->label(__('requests.fields.title'))->columnSpanFull()->weight('bold'),
                TextEntry::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (ConsignmentStatus $state) => $state->label()),
                TextEntry::make('category.name_ar')->label(__('requests.fields.category_id'))->placeholder('—'),
                TextEntry::make('description')->label(__('requests.fields.description'))->columnSpanFull()->extraAttributes(['class' => 'whitespace-pre-line']),
                TextEntry::make('asking_price')->label(__('requests.fields.asking_price'))
                    ->formatStateUsing(fn (?string $state) => $state === null ? '—' : Money::amount($state).' '.Money::currency())->placeholder('—'),
                TextEntry::make('decision_note')->label(__('admin.requests.decision_note'))->placeholder('—'),
                ViewEntry::make('photos')->label(__('requests.fields.photos'))->view('filament.request-photos')
                    ->viewData(['collection' => ConsignmentRequest::MEDIA_PHOTOS])->columnSpanFull(),
            ]),
            Section::make(__('requests.contact'))->columnSpan(1)->schema([
                TextEntry::make('reference')->label(__('requests.reference'))->copyable(),
                TextEntry::make('name')->label(__('requests.fields.name')),
                TextEntry::make('phone')->label(__('requests.fields.phone'))
                    ->formatStateUsing(fn (string $state) => SaudiMobile::local($state))->url(fn (ConsignmentRequest $record) => 'tel:'.$record->phone),
                TextEntry::make('email')->label(__('requests.fields.email'))->placeholder('—'),
                TextEntry::make('city')->label(__('requests.fields.city')),
                TextEntry::make('reviewer.name')->label(__('admin.requests.reviewer'))->placeholder('—'),
                TextEntry::make('admin_notes')->label(__('admin.requests.admin_notes'))->placeholder('—')->extraAttributes(['class' => 'whitespace-pre-line']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference')->label(__('requests.reference'))->searchable(),
                TextColumn::make('title')->label(__('requests.fields.title'))->searchable()->wrap()
                    ->description(fn (ConsignmentRequest $record) => $record->city),
                TextColumn::make('name')->label(__('requests.fields.name'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('name', 'like', "%{$search}%")->orWhere('phone', SaudiMobile::normalize($search) ?? $search)))
                    ->description(fn (ConsignmentRequest $record) => SaudiMobile::local($record->phone)),
                TextColumn::make('asking_price')->label(__('requests.fields.asking_price'))
                    ->formatStateUsing(fn (?string $state) => $state === null ? '—' : Money::amount($state).' '.Money::currency())->placeholder('—'),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (ConsignmentStatus $state) => $state->label())
                    ->color(fn (ConsignmentStatus $state) => match ($state) {
                        ConsignmentStatus::New => 'warning',
                        ConsignmentStatus::Reviewing => 'info',
                        ConsignmentStatus::Approved => 'success',
                        ConsignmentStatus::Rejected => 'gray',
                    }),
                TextColumn::make('created_at')->label(__('admin.fields.date'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->multiple()
                    ->options(EnumLabels::options(ConsignmentStatus::class)),
            ])
            ->recordActions([ViewAction::make(), DeleteAction::make()]);
    }

    /** @return Builder<Model> */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->with(['category', 'reviewer', 'media']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsignmentRequests::route('/'),
            'view' => ViewConsignmentRequest::route('/{record}'),
        ];
    }
}
