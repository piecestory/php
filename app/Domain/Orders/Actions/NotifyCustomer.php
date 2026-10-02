<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\OrderNotice;
use App\Domain\Orders\Models\Order;
use App\Domain\Settings\StoreSettings;
use App\Jobs\SendSms;
use App\Mail\OrderNoticeMail;
use App\Mail\StoreOrderAlertMail;
use App\Support\Orders\OrderLink;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

/** Tells the customer (SMS always, email when given) and, for new business, the store team. Everything is queued. */
final class NotifyCustomer
{
    public function __construct(private readonly StoreSettings $settings) {}

    public function handle(Order $order, OrderNotice $notice): void
    {
        SendSms::dispatch($order->phone, __("orders.notice.{$notice->value}.sms", self::replacements($order), $order->locale));

        if ($order->email !== null) {
            Mail::to($order->email)->locale($order->locale)->queue(new OrderNoticeMail($order, $notice));
        }

        $storeEmail = $this->settings->get('store.email');
        if ($notice->alertsStore() && $storeEmail !== null) {
            Mail::to($storeEmail)->locale('ar')->queue(new StoreOrderAlertMail($order, $notice));
        }
    }

    /**
     * Placeholders shared by the SMS and email texts, in the customer's language.
     *
     * @return array<string, string>
     */
    public static function replacements(Order $order): array
    {
        $date = fn (?CarbonInterface $at) => $at?->locale($order->locale)->translatedFormat('j F') ?? '';

        return [
            'number' => $order->number,
            'name' => $order->customer_name,
            'until' => $date($order->reserved_until),
            'deadline' => $date($order->hold_expires_at),
            'link' => OrderLink::page($order),
        ];
    }
}
