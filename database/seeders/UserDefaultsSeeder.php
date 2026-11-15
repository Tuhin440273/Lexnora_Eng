<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Learning\Listeners\CreateUserDefaults;
use Illuminate\Database\Seeder;

class UserDefaultsSeeder extends Seeder
{
    /** Gives profile + default role to accounts created before Phase 2. */
    public function run(): void
    {
        User::query()->with('roles')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                CreateUserDefaults::provision($user);
            }
        });
    }
}
