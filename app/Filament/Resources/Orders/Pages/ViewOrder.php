<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Orders\Actions\CancelOrder;
use App\Domain\Orders\Actions\FulfilOrder;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\RecordInStorePayment;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\Staff;
use App\Support\Money\Money;
use App\Support\Orders\OrderLink;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use LogicException;

/** @property Order $record */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('admin.orders.title', ['number' => $this->order()->number]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->recordPaymentAction(),
            $this->startPreparingAction(),
            $this->shipAction(),
            $this->deliveredAction(),
            ActionGroup::make([
                Action::make('customerPage')->label(__('admin.orders.customer_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => OrderLink::page($this->order()), shouldOpenInNewTab: true),
                $this->cancelAction(),
            ]),
        ];
    }

    private function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
            ->label(__('admin.orders.record_payment'))
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn () => in_array($this->order()->status, [OrderStatus::Pending, OrderStatus::Reserved], true)
                && bccomp($this->order()->balanceDue(), '0', 2) > 0)
            ->requiresConfirmation()
            ->modalHeading(__('admin.orders.record_payment'))
            ->modalDescription(fn () => __('admin.orders.record_payment_hint', [
                'amount' => Money::amount($this->order()->nextPaymentAmount()).' '.Money::currency(),
            ]))
            ->schema([
                Textarea::make('note')->label(__('admin.orders.payment_note'))->placeholder(__('admin.orders.payment_note_placeholder'))->maxLength(500)->rows(2),
            ])
            ->action(function (array $data, RecordInStorePayment $recordPayment): void {
                try {
                    $recordPayment->handle($this->order(), Staff::id(), $data['note'] ?? null);
                } catch (PaymentException $e) {
                    Notification::make()->danger()->title(__($e->translationKey()))->send();

                    return;
                }

                $this->refreshOrder();
                Notification::make()->success()->title(__('admin.orders.payment_recorded'))->send();
            });
    }

    private function startPreparingAction(): Action
    {
        return Action::make('startPreparing')
            ->label(__('admin.orders.start_preparing'))
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->visible(fn () => $this->order()->status === OrderStatus::Confirmed)
            ->requiresConfirmation()
            ->action(function (FulfilOrder $fulfil): void {
                $fulfil->startPreparing($this->order(), Staff::id());
                $this->refreshOrder();
            })
            ->successNotificationTitle(__('admin.orders.updated'));
    }

    private function shipAction(): Action
    {
        return Action::make('ship')
            ->label(__('admin.orders.ship'))
            ->icon(Heroicon::OutlinedTruck)
            ->visible(fn () => $this->order()->status === OrderStatus::Processing && $this->order()->pickup_branch_id === null)
            ->schema([
                TextInput::make('carrier')->label(__('admin.orders.carrier'))->required()->maxLength(50),
                TextInput::make('tracking_number')->label(__('admin.orders.tracking_number'))->maxLength(100)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('tracking_url')->label(__('admin.orders.tracking_url'))->url()->maxLength(500)->extraInputAttributes(['dir' => 'ltr']),
            ])
            ->modalDescription(__('admin.orders.ship_hint'))
            ->action(function (array $data, FulfilOrder $fulfil): void {
                $fulfil->ship($this->order(), (string) $data['carrier'], $data['tracking_number'] ?: null, $data['tracking_url'] ?: null, Staff::id());
                $this->refreshOrder();
            })
            ->successNotificationTitle(__('admin.orders.updated'));
    }

    private function deliveredAction(): Action
    {
        $order = fn () => $this->order();

        return Action::make('delivered')
            ->label(fn () => $order()->pickup_branch_id !== null ? __('admin.orders.picked_up') : __('admin.orders.delivered'))
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn () => $order()->status === OrderStatus::Shipped
                || ($order()->status === OrderStatus::Processing && $order()->pickup_branch_id !== null))
            ->requiresConfirmation()
            ->action(function (FulfilOrder $fulfil): void {
                $fulfil->markDelivered($this->order(), Staff::id());
                $this->refreshOrder();
            })
            ->successNotificationTitle(__('admin.orders.updated'));
    }

    private function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('admin.orders.cancel'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn () => $this->order()->status->canTransitionTo(OrderStatus::Cancelled))
            ->requiresConfirmation()
            ->modalDescription(__('admin.orders.cancel_hint'))
            ->schema([
                Textarea::make('reason')->label(__('admin.orders.cancel_reason'))->required()->maxLength(500)->rows(2),
            ])
            ->action(function (array $data, CancelOrder $cancel): void {
                $cancel->handle($this->order(), (string) $data['reason'], Staff::id());
                $this->refreshOrder();
            })
            ->successNotificationTitle(__('admin.orders.cancelled'));
    }

    private function order(): Order
    {
        $record = $this->getRecord();

        return $record instanceof Order ? $record : throw new LogicException('Order page without an order.');
    }

    private function refreshOrder(): void
    {
        $this->order()->refresh()->load(OrderResource::DETAIL_RELATIONS);
    }
}
