<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Exceptions\StaffChangeRefused;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Adds or updates a team member and their role. Guards: nobody can change their own role or
 * switch themselves off, and the store always keeps at least one active system admin.
 */
final class SaveStaffMember
{
    public function __construct(private readonly RevokeSessions $revoke) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, password?: ?string, role: Role, is_active: bool}  $data
     *
     * @throws StaffChangeRefused
     */
    public function handle(array $data, User $actor, ?User $member = null, ?string $actorSessionId = null): User
    {
        return DB::transaction(function () use ($data, $actor, $member, $actorSessionId): User {
            if ($member !== null) {
                $this->guard($member, $actor, $data['role'], $data['is_active']);
            }

            $member ??= new User(['locale' => 'ar']);
            $member->fill([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? $member->phone,
            ]);
            if (filled($data['password'] ?? null)) {
                $member->password = $data['password'];
            }
            $member->is_active = $data['is_active'];
            $member->save();

            $member->syncRoles([$data['role']->value]);

            if (! $member->is_active || filled($data['password'] ?? null)) {
                $this->revoke->handle($member, $member->is($actor) ? $actorSessionId : null);
            }

            return $member;
        });
    }

    /** @throws StaffChangeRefused */
    private function guard(User $member, User $actor, Role $role, bool $active): void
    {
        $currentRole = $member->roles()->value('name');

        if ($member->is($actor) && ($currentRole !== $role->value || ! $active)) {
            throw StaffChangeRefused::ownAccess();
        }

        $losesAdmin = $currentRole === Role::Admin->value && ($role !== Role::Admin || ! $active);
        if ($losesAdmin) {
            $otherAdmins = User::role(Role::Admin->value)->where('is_active', true)->whereKeyNot($member->id)->lockForUpdate()->count();
            if ($otherAdmins === 0) {
                throw StaffChangeRefused::lastAdmin();
            }
        }
    }
}
