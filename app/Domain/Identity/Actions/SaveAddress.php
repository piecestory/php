<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\AddressLimitReached;
use App\Domain\Identity\Models\Address;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Adds or updates an address in the customer's address book. Exactly one address is the default
 * (used to prefill checkout): the first one saved, or whichever the customer marks.
 */
final class SaveAddress
{
    public const int MAX_ADDRESSES = 10;

    /**
     * @param  array{label?: ?string, recipient_name: string, phone: string, city: string, district: string, street: string, building_number: string, postal_code: string, additional_number?: ?string, short_address?: ?string, notes?: ?string}  $data  validated; phone in E.164
     *
     * @throws AddressLimitReached
     */
    public function handle(User $user, array $data, bool $makeDefault = false, ?Address $address = null): Address
    {
        return DB::transaction(function () use ($user, $data, $makeDefault, $address): Address {
            // Serialises concurrent saves for the same customer (limit and single default stay correct).
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $count = $user->addresses()->count();
            if ($address === null && $count >= self::MAX_ADDRESSES) {
                throw new AddressLimitReached;
            }

            $address ??= $user->addresses()->make();
            $address->fill($data);

            $makeDefault = $makeDefault || $count === 0 || ($address->exists && $address->is_default);
            if ($makeDefault) {
                $user->addresses()->whereKeyNot($address->id ?? 0)->update(['is_default' => false]);
            }
            $address->is_default = $makeDefault;
            $address->save();

            return $address;
        });
    }
}
