<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Exceptions\InvalidLoginCode;
use App\Domain\Identity\Models\OneTimePassword;
use Illuminate\Support\Facades\Hash;

/**
 * Checks a login code. A code works once, expires after a few minutes and is burned after
 * too many wrong guesses (brute force on a 6-digit code is not feasible).
 */
final class VerifyLoginCode
{
    public const int MAX_ATTEMPTS = 5;

    /** @throws InvalidLoginCode */
    public function handle(string $phone, string $code): void
    {
        $otp = OneTimePassword::query()
            ->where('phone', $phone)
            ->where('purpose', OtpPurpose::Login)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($otp === null) {
            throw InvalidLoginCode::expired();
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->attempts++;
            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->consumed_at = now();
            }
            $otp->save();

            throw $otp->consumed_at !== null ? InvalidLoginCode::expired() : InvalidLoginCode::wrong();
        }

        $otp->forceFill(['consumed_at' => now()])->save();
    }
}
