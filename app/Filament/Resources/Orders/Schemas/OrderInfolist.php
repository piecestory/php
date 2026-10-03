<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Domain\Orders\Enums\OrderPaymentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\OrderType;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentPurpose;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\EnumLabels;
use App\Support\Money\Money;
use App\Support\Phone\SaudiMobile;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?string $state) => $state === null ? '—' : Money::amount($state).' '.Money::currency();
        $when = 'j M Y، g:i a';

        return $schema->columns(3)->components([
            Section::make(__('admin.orders.summary'))->columnSpan(2)->columns(3)->schema([
                TextEntry::make('type')->label(__('admin.orders.type'))->formatStateUsing(fn (OrderType $state) => EnumLabels::of($state)),
                TextEntry::make('status')->label(__('admin.fields.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => EnumLabels::of($state))
                    ->color(fn (OrderStatus $state) => OrderResource::statusColor($state)),
                TextEntry::make('payment_status')->label(__('admin.orders.payment'))->badge()
                    ->formatStateUsing(fn (OrderPaymentStatus $state) => EnumLabels::of($state)),
                TextEntry::make('placed_at')->label(__('admin.fields.date'))->dateTime($when),
                TextEntry::make('reserved_until')->label(__('admin.orders.reserved_until'))->dateTime($when)->placeholder('—'),
                TextEntry::make('hold_expires_at')->label(__('admin.orders.hold_expires_at'))->dateTime($when)->placeholder('—')
                    ->helperText(__('admin.orders.hold_expires_hint')),
            ]),

            Section::make(__('admin.orders.customer'))->columnSpan(1)->schema([
                TextEntry::make('customer_name')->label(__('admin.fields.name')),
                TextEntry::make('phone')->label(__('admin.fields.phone'))
                    ->formatStateUsing(fn (string $state) => SaudiMobile::local($state))
                    ->url(fn (Order $record) => 'tel:'.$record->phone),
                TextEntry::make('email')->label(__('admin.fields.email'))->placeholder('—')->copyable(),
                TextEntry::make('user.name')->label(__('admin.orders.account'))->placeholder(__('admin.orders.guest')),
            ]),

            Section::make(__('orders.page.items'))->columnSpan(2)->schema([
                RepeatableEntry::make('items')->hiddenLabel()->contained(false)->table([
                    RepeatableEntry\TableColumn::make(__('admin.fields.name')),
                    RepeatableEntry\TableColumn::make(__('admin.products.sku')),
                    RepeatableEntry\TableColumn::make(__('admin.orders.quantity')),
                    RepeatableEntry\TableColumn::make(__('orders.page.total')),
                ])->schema([
                    TextEntry::make('name_ar'),
                    TextEntry::make('sku'),
                    TextEntry::make('quantity'),
                    TextEntry::make('line_total')->formatStateUsing($money),
                ]),
            ]),

            Section::make(__('admin.orders.totals'))->columnSpan(1)->schema([
                TextEntry::make('subtotal')->label(__('orders.page.subtotal'))->formatStateUsing($money)->inlineLabel(),
                TextEntry::make('shipping_total')->label(__('orders.page.shipping'))->formatStateUsing($money)->inlineLabel(),
                TextEntry::make('grand_total')->label(__('orders.page.total'))->formatStateUsing($money)->inlineLabel()->weight('bold'),
                TextEntry::make('tax_total')->label(__('orders.page.vat'))->formatStateUsing($money)->inlineLabel(),
                TextEntry::make('deposit_total')->label(__('orders.page.deposit'))->formatStateUsing($money)->inlineLabel()
                    ->visible(fn (Order $record) => $record->type === OrderType::DepositReservation),
                TextEntry::make('amount_paid')->label(__('orders.page.paid'))->formatStateUsing($money)->inlineLabel(),
                TextEntry::make('balance')->label(__('orders.page.balance'))->inlineLabel()
                    ->state(fn (Order $record) => $money($record->balanceDue())),
            ]),

            Section::make(__('orders.page.fulfilment'))->columnSpan(2)->columns(2)->schema([
                TextEntry::make('pickupBranch.name_ar')->label(__('admin.orders.pickup_branch'))
                    ->visible(fn (Order $record) => $record->pickup_branch_id !== null),
                TextEntry::make('address')->label(__('orders.page.delivery_to'))
                    ->visible(fn (Order $record) => $record->pickup_branch_id === null)
                    ->state(fn (Order $record) => collect([$record->ship_street, $record->ship_district, $record->ship_city])->filter()->implode('، ')
                        .' — '.collect([$record->ship_building_number, $record->ship_postal_code, $record->ship_additional_number, $record->ship_short_address])->filter()->implode(' · ')),
                TextEntry::make('customer_note')->label(__('admin.orders.customer_note'))->placeholder('—')->columnSpanFull(),
                RepeatableEntry::make('shipments')->label(__('admin.orders.shipments'))->columnSpanFull()->columns(3)
                    ->visible(fn (Order $record) => $record->shipments->isNotEmpty())
                    ->schema([
                        TextEntry::make('carrier')->label(__('admin.orders.carrier')),
                        TextEntry::make('tracking_number')->label(__('admin.orders.tracking_number'))->placeholder('—')->copyable(),
                        TextEntry::make('shipped_at')->label(__('admin.orders.shipped_at'))->dateTime($when),
                    ]),
            ]),

            Section::make(__('admin.orders.history'))->columnSpan(1)->schema([
                RepeatableEntry::make('statusChanges')->hiddenLabel()->contained(false)->schema([
                    TextEntry::make('to_status')->hiddenLabel()->weight('medium')
                        ->formatStateUsing(fn (string $state) => EnumLabels::of(OrderStatus::from($state)))
                        ->helperText(fn ($record) => trim(($record->created_at?->translatedFormat($when) ?? '').' · '.($record->author->name ?? __('admin.stock.system')), ' ·')),
                ]),
            ]),

            Section::make(__('admin.orders.payments'))->columnSpanFull()->visible(fn (Order $record) => $record->payments->isNotEmpty())->schema([
                RepeatableEntry::make('payments')->hiddenLabel()->columns(5)->schema([
                    TextEntry::make('method')->label(__('admin.orders.method'))->formatStateUsing(fn (PaymentMethod $state) => $state->label()),
                    TextEntry::make('purpose')->label(__('admin.orders.purpose'))->formatStateUsing(fn (PaymentPurpose $state) => EnumLabels::of($state)),
                    TextEntry::make('amount')->label(__('admin.orders.amount'))->formatStateUsing($money),
                    TextEntry::make('status')->label(__('admin.fields.status'))->badge()
                        ->formatStateUsing(fn (PaymentStatus $state) => EnumLabels::of($state))
                        ->color(fn (PaymentStatus $state) => match ($state) {
                            PaymentStatus::Captured => 'success',
                            PaymentStatus::Failed => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('paid_at')->label(__('admin.orders.paid_at'))->dateTime($when)->placeholder('—'),
                ]),
            ]),
        ]);
    }
}
