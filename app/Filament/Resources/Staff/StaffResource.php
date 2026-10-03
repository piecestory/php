<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use App\Rules\SaudiMobileNumber;
use App\Support\Phone\SaudiMobile;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/** The team: who can sign in to this panel and with which role. Accounts are switched off, never deleted. */
class StaffResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 9;

    protected static function permission(): Permission
    {
        return Permission::ManageUsers;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['delete', 'deleteAny', 'replicate'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.staff.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.staff.plural');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('roles')->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label(__('admin.fields.name'))->required()->maxLength(100),
                TextInput::make('email')->label(__('admin.fields.email'))->helperText(__('admin.staff.email_hint'))
                    ->email()->required()->maxLength(255)->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('phone')->label(__('admin.fields.phone'))->tel()->rule(new SaudiMobileNumber)
                    ->dehydrateStateUsing(fn (?string $state) => SaudiMobile::normalize($state))
                    ->formatStateUsing(fn (?string $state) => $state ? SaudiMobile::local($state) : null)
                    ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                Select::make('role')->label(__('admin.staff.role'))->options(EnumLabels::options(Role::class))->required()
                    ->helperText(__('admin.staff.role_hint')),
                TextInput::make('password')->label(__('admin.staff.password'))->password()->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (?User $record) => $record === null)
                    ->helperText(fn (?User $record) => $record ? __('admin.staff.password_keep') : __('admin.staff.password_share')),
                Toggle::make('is_active')->label(__('admin.fields.is_active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin.fields.name'))->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('roles.name')->label(__('admin.staff.role'))->badge()
                    ->formatStateUsing(fn (string $state) => EnumLabels::of(Role::from($state))),
                TextColumn::make('last_login_at')->label(__('admin.customers.last_login'))->since()->placeholder('—'),
                IconColumn::make('is_active')->label(__('admin.fields.is_active'))->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
