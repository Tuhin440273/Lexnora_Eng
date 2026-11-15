<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Exceptions\CannotChangeUser;
use Illuminate\Support\Facades\DB;

/**
 * Account changes made by staff. The rules protect against lock-out: nobody
 * edits their own account here, only a super-admin touches admins, and the last
 * active super-admin can never be removed.
 */
class UserAdministrationService
{
    public const ROLES = ['super-admin', 'admin', 'content-manager', 'learner'];

    /**
     * @throws CannotChangeUser
     */
    public function setActive(User $actor, User $target, bool $active): void
    {
        $this->authorizeChange($actor, $target);

        if (! $active) {
            $this->assertNotLastSuperAdmin($target);
        }

        $target->forceFill(['deactivated_at' => $active ? null : now()])->save();
    }

    /**
     * @throws CannotChangeUser
     */
    public function setRole(User $actor, User $target, string $role): void
    {
        $this->authorizeChange($actor, $target);

        if (! in_array($role, self::ROLES, true)) {
            throw new CannotChangeUser(__('Unknown role.'));
        }

        if (in_array($role, ['super-admin', 'admin'], true) && ! $actor->hasRole('super-admin')) {
            throw new CannotChangeUser(__('Only a super-admin can give the admin roles.'));
        }

        if ($role !== 'super-admin') {
            $this->assertNotLastSuperAdmin($target);
        }

        DB::transaction(fn () => $target->syncRoles([$role]));
    }

    /**
     * @throws CannotChangeUser
     */
    private function authorizeChange(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new CannotChangeUser(__('You cannot change your own account here.'));
        }

        if ($target->hasAnyRole(['super-admin', 'admin']) && ! $actor->hasRole('super-admin')) {
            throw new CannotChangeUser(__('Only a super-admin can change an admin.'));
        }
    }

    /**
     * @throws CannotChangeUser
     */
    private function assertNotLastSuperAdmin(User $target): void
    {
        if (! $target->hasRole('super-admin')) {
            return;
        }

        $others = User::role('super-admin')->whereNull('deactivated_at')->whereKeyNot($target->id)->count();

        if ($others === 0) {
            throw new CannotChangeUser(__('This is the last active super-admin, so it cannot be removed.'));
        }
    }
}
