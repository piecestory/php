<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Domain\Settings\StoreSettings;
use App\Domain\Shared\Contracts\ServiceRequest;
use App\Jobs\SendSms;
use App\Mail\RequestNoticeMail;
use App\Mail\StoreRequestAlertMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/** Keeps a customer informed about their request (SMS always, email when given). Queued, after commit. */
final class NotifyRequester
{
    public function __construct(private readonly StoreSettings $settings) {}

    /** @param  string  $event  e.g. "received", "approved", "offer_sent" (texts in lang/requests.php) */
    public function handle(Model&ServiceRequest $request, string $event, ?string $message = null): void
    {
        $key = "requests.{$request->requestKind()}.{$event}";

        SendSms::dispatch($request->contactPhone(), __("{$key}.sms", ['reference' => $request->requestReference()], $request->messageLocale()));

        if (($email = $request->contactEmail()) !== null) {
            Mail::to($email)->locale($request->messageLocale())->queue(new RequestNoticeMail($request, $event, $message));
        }
    }

    public function alertStore(Model&ServiceRequest $request): void
    {
        $storeEmail = $this->settings->get('store.email');
        if ($storeEmail !== null) {
            Mail::to($storeEmail)->locale('ar')->queue(new StoreRequestAlertMail($request));
        }
    }
}
