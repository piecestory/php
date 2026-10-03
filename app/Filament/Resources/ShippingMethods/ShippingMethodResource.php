<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingMethods;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Shipping\Models\ShippingMethod;
use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Filament\Support\RequiresPermission;
use App\Support\Money\Money;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Pickup and delivery are fixed methods (checkout relies on their codes): staff edit prices, the
 * free-delivery threshold, texts and availability, but cannot add or remove methods.
 */
class ShippingMethodResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 2;

    protected static function permission(): Permission
    {
        return Permission::ManageSettings;
    }

    /** @return list<string> */
    protected static function forbiddenAbilities(): array
    {
        return ['create', 'delete', 'deleteAny'];
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('admin.shipping.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.shipping.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $isDelivery = fn (?ShippingMethod $record) => $record?->code === ShippingMethod::DELIVERY;

        return $schema->columns(2)->components([
            TextInput::make('name_ar')->label(__('admin.fields.name_ar'))->required()->maxLength(190),
            TextInput::make('name_en')->label(__('admin.fields.name_en'))->required()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('description_ar')->label(__('admin.fields.description_ar'))->maxLength(255)->rows(2),
            Textarea::make('description_en')->label(__('admin.fields.description_en'))->maxLength(255)->rows(2)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('rate')->label(__('admin.shipping.rate'))->helperText(__('admin.products.vat_inclusive'))
                ->numeric()->minValue(0)->maxValue(10000)->required()->suffix(__('ui.currency'))->visible($isDelivery),
            TextInput::make('free_shipping_threshold')->label(__('admin.shipping.free_threshold'))->helperText(__('admin.shipping.free_threshold_hint'))
                ->numeric()->minValue(0)->suffix(__('ui.currency'))->visible($isDelivery),
            // Delivery cannot go live at 0 SAR by accident: a price must be set first.
            Toggle::make('is_active')->label(__('admin.shipping.is_active'))
                ->rule(fn (Get $get, ?ShippingMethod $record) => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                    if ($value && $record?->code === ShippingMethod::DELIVERY && bccomp((string) ($get('rate') ?: '0'), '0', 2) <= 0) {
                        $fail(__('admin.shipping.needs_rate'));
                    }
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_ar')->label(__('admin.fields.name')),
                TextColumn::make('rate')->label(__('admin.shipping.rate'))
                    ->formatStateUsing(fn (ShippingMethod $record) => bccomp((string) $record->rate, '0', 2) === 0 ? __('checkout.free') : Money::amount((string) $record->rate).' '.Money::currency()),
                TextColumn::make('free_shipping_threshold')->label(__('admin.shipping.free_threshold'))
                    ->formatStateUsing(fn (?string $state) => $state === null ? '—' : __('checkout.free_over', ['amount' => Money::amount($state).' '.Money::currency()])),
                ToggleColumn::make('is_active')->label(__('admin.shipping.is_active'))
                    ->disabled(fn (ShippingMethod $record) => $record->code === ShippingMethod::DELIVERY && bccomp((string) $record->rate, '0', 2) <= 0),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShippingMethods::route('/')];
    }
}
