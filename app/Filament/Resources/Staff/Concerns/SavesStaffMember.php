<?php

declare(strict_types=1);

namespace App\Filament\Resources\Staff\Concerns;

use App\Domain\Identity\Actions\SaveStaffMember;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Exceptions\StaffChangeRefused;
use App\Domain\Identity\Models\User;
use App\Filament\Support\Staff;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

/** Create and edit pages both save through the domain action (role guards live there, not in the form). */
trait SavesStaffMember
{
    /** @param  array<string, mixed>  $data */
    protected function saveMember(array $data, ?User $member): User
    {
        try {
            return app(SaveStaffMember::class)->handle([
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? null,
                'role' => Role::from((string) $data['role']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ], Staff::user(), $member, request()->hasSession() ? request()->session()->getId() : null);
        } catch (StaffChangeRefused $e) {
            Notification::make()->danger()->title(__($e->translationKey()))->send();

            throw new Halt;
        }
    }
}
