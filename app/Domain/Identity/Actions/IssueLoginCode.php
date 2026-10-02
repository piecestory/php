<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Models\OneTimePassword;
use App\Domain\Notifications\Sms\SmsGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Sends a one-time login code by SMS. Only the hash is stored; earlier unused codes are invalidated. */
final class IssueLoginCode
{
    public const int LENGTH = 6;

    public const int TTL_MINUTES = 5;

    public function __construct(private readonly SmsGateway $sms) {}

    public function handle(string $phone, ?string $ipAddress = null): void
    {
        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($phone, $code, $ipAddress): void {
            OneTimePassword::query()
                ->where('phone', $phone)
                ->where('purpose', OtpPurpose::Login)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            OneTimePassword::query()->create([
                'phone' => $phone,
                'purpose' => OtpPurpose::Login,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'ip_address' => $ipAddress,
            ]);
        });

        $this->sms->send($phone, __('auth.otp.sms', ['code' => $code, 'minutes' => self::TTL_MINUTES]));
    }
}
