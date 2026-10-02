<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'label', 'recipient_name', 'phone', 'city', 'district', 'street', 'building_number',
    'postal_code', 'additional_number', 'short_address', 'notes', 'is_default',
])]
#[UseFactory(AddressFactory::class)]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
