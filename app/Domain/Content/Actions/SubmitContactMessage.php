<?php

declare(strict_types=1);

namespace App\Domain\Content\Actions;

use App\Domain\Content\Enums\ContactMessageStatus;
use App\Domain\Content\Models\ContactMessage;
use App\Domain\Settings\StoreSettings;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

/** Stores a visitor's message for the team (admin → messages) and forwards it to the store mailbox. */
final class SubmitContactMessage
{
    public function __construct(private readonly StoreSettings $settings) {}

    /** @param  array{name: string, phone: ?string, email: ?string, subject: ?string, message: string}  $data  validated; phone in E.164 */
    public function handle(array $data, ?string $ipAddress): ContactMessage
    {
        $message = ContactMessage::query()->create([
            ...$data,
            'status' => ContactMessageStatus::New,
            'ip_address' => $ipAddress,
        ]);

        $storeEmail = $this->settings->get('store.email');
        if ($storeEmail !== null) {
            Mail::to($storeEmail)->locale('ar')->queue(new ContactMessageMail($message));
        }

        return $message;
    }
}
