<?php

declare(strict_types=1);

namespace App\Filament\Resources\Branches;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Store\Enums\BranchType;
use App\Domain\Store\Models\Branch;
use App\Filament\Resources\Branches\Pages\ManageBranches;
use App\Filament\Support\EnumLabels;
use App\Filament\Support\RequiresPermission;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Showrooms (pickup points, shown in the footer) and the warehouse. Branches are switched off, never deleted. */
class BranchResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 3;

    protected static function permission(): Permission
    {
        return Permission::ManageSettings;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['delete', 'deleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.branches.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.branches.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name_ar')->label(__('admin.fields.name_ar'))->required()->maxLength(190),
            TextInput::make('name_en')->label(__('admin.fields.name_en'))->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            Select::make('type')->label(__('admin.branches.type'))->options(EnumLabels::options(BranchType::class))->required()->default(BranchType::Showroom->value),
            TextInput::make('city')->label(__('checkout.attributes.address.city'))->required()->maxLength(100)->default('جدة'),
            TextInput::make('district')->label(__('checkout.attributes.address.district'))->required()->maxLength(100),
            TextInput::make('phone')->label(__('admin.fields.phone'))->tel()->regex('/^[0-9+][0-9 ()-]{6,19}$/')->maxLength(20)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('address_ar')->label(__('admin.branches.address_ar'))->maxLength(190),
            TextInput::make('address_en')->label(__('admin.branches.address_en'))->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('map_url')->label(__('admin.branches.map_url'))->helperText(__('admin.branches.map_url_hint'))
                ->url()->maxLength(500)->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
            TextInput::make('sort_order')->label(__('admin.fields.sort_order'))->integer()->minValue(0)->default(0),
            Toggle::make('is_pickup_point')->label(__('admin.branches.is_pickup_point'))->helperText(__('admin.branches.is_pickup_point_hint')),
            Toggle::make('is_active')->label(__('admin.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_ar')->label(__('admin.fields.name'))->description(fn (Branch $record) => EnumLabels::of($record->type)),
                TextColumn::make('district')->label(__('checkout.attributes.address.district')),
                TextColumn::make('phone')->label(__('admin.fields.phone'))->placeholder('—'),
                IconColumn::make('is_pickup_point')->label(__('admin.branches.is_pickup_point'))->boolean(),
                ToggleColumn::make('is_active')->label(__('admin.fields.is_active')),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBranches::route('/')];
    }
}
