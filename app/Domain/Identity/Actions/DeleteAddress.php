<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Address;
use Illuminate\Support\Facades\DB;

/** Removes an address; if it was the default, the most recently added remaining address takes over. */
final class DeleteAddress
{
    public function handle(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            $address->delete();

            if ($address->is_default) {
                Address::query()->where('user_id', $address->user_id)->latest('id')->first()?->forceFill(['is_default' => true])->save();
            }
        });
    }
}
