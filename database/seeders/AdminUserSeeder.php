<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates (or updates) the super-admin from ADMIN_EMAIL / ADMIN_PASSWORD.
     * Without a password nothing is created, so no default password ever ships.
     */
    public function run(): void
    {
        $email = config('learning.admin.email');
        $password = config('learning.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_PASSWORD is empty: admin user was not created.');

            return;
        }

        $user = User::firstOrNew(['email' => strtolower($email)]);
        $user->forceFill([
            'name' => config('learning.admin.name'),
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles([Role::findOrCreate('super-admin', 'web')]);
        $user->ensureProfile();
    }
}
