<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers;

use App\Domain\Identity\Actions\RevokeSessions;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Support\RequiresPermission;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Customer accounts (users without a staff role). Read-only, except switching an account off. */
class CustomerResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static function permission(): Permission
    {
        return Permission::ManageCustomers;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'update', 'delete', 'deleteAny', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.customers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.customers.plural');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereDoesntHave('roles');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('admin.customers.details'))->columnSpan(2)->columns(2)->schema([
                TextEntry::make('name')->label(__('admin.fields.name')),
                TextEntry::make('phone')->label(__('admin.fields.phone'))->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? SaudiMobile::local($state) : '—'),
                TextEntry::make('email')->label(__('admin.fields.email'))->placeholder('—'),
                TextEntry::make('locale')->label(__('account.profile.fields.locale'))->formatStateUsing(fn (string $state) => __("account.profile.locales.{$state}")),
                TextEntry::make('created_at')->label(__('admin.customers.joined'))->dateTime('j M Y'),
                TextEntry::make('last_login_at')->label(__('admin.customers.last_login'))->dateTime('j M Y، g:i a')->placeholder('—'),
                IconEntry::make('is_active')->label(__('admin.fields.is_active'))->boolean(),
            ]),
            Section::make(__('account.nav.addresses'))->columnSpan(1)->schema([
                RepeatableEntry::make('addresses')->hiddenLabel()->contained(false)->placeholder(__('account.addresses.empty'))->schema([
                    TextEntry::make('line')->hiddenLabel()->state(fn ($record) => collect([$record->label, $record->street, $record->district, $record->city])->filter()->implode('، ')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('admin.fields.name'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', SaudiMobile::normalize($search) ?? $search)))
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label(__('admin.fields.phone'))->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? SaudiMobile::local($state) : '—'),
                TextColumn::make('orders_count')->label(__('admin.orders.plural'))->counts('orders')->sortable(),
                TextColumn::make('created_at')->label(__('admin.customers.joined'))->date('j M Y')->sortable(),
                IconColumn::make('is_active')->label(__('admin.fields.is_active'))->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label(__('admin.fields.is_active'))])
            ->recordActions([ViewAction::make()]);
    }

    /** Switching an account off signs the customer out everywhere; their orders stay as they are. */
    public static function toggleActiveAction(): Action
    {
        return Action::make('toggleActive')
            ->label(fn (User $record) => $record->is_active ? __('admin.customers.deactivate') : __('admin.customers.activate'))
            ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
            ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalDescription(fn (User $record) => $record->is_active ? __('admin.customers.deactivate_hint') : null)
            ->action(function (User $record, RevokeSessions $revoke): void {
                $record->forceFill(['is_active' => ! $record->is_active])->save();
                if (! $record->is_active) {
                    $revoke->handle($record);
                }
            })
            ->successNotificationTitle(__('admin.customers.updated'));
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
