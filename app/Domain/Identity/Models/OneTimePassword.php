<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['phone', 'purpose', 'code_hash', 'attempts', 'expires_at', 'consumed_at', 'ip_address'])]
#[Hidden(['code_hash'])]
class OneTimePassword extends Model
{
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
