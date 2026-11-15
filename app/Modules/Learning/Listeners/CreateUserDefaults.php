<?php

namespace App\Modules\Learning\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Models\Role;

class CreateUserDefaults
{
    public function handle(Registered $event): void
    {
        if ($event->user instanceof User) {
            self::provision($event->user);
        }
    }

    /**
     * Idempotent: safe to call from the listener, the seeders and tests.
     * The default role is only given to users that have no role yet,
     * so admins keep their own role.
     */
    public static function provision(User $user): void
    {
        $user->ensureProfile();

        if ($user->roles()->doesntExist()) {
            $role = Role::findOrCreate((string) config('learning.default_role', 'learner'), 'web');
            $user->assignRole($role);
        }
    }
}
