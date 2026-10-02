<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Address;
use Illuminate\Support\Facades\DB;

/** Makes this the address used to prefill checkout; the customer's other addresses stop being default. */
final class SetDefaultAddress
{
    public function handle(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            Address::query()->where('user_id', $address->user_id)->whereKeyNot($address->id)->update(['is_default' => false]);
            $address->forceFill(['is_default' => true])->save();
        });
    }
}
