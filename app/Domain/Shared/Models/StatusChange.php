<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_status', 'to_status', 'changed_by', 'note'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
