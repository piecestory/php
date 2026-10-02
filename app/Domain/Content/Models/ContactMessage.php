<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\ContactMessageStatus;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'phone', 'email', 'subject', 'message', 'status', 'handled_by', 'ip_address'])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return ['status' => ContactMessageStatus::class];
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
