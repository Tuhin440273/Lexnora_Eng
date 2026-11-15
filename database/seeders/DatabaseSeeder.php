<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            BadgeSeeder::class,
            AdminUserSeeder::class,
            UserDefaultsSeeder::class,
        ]);

        // Demo courses are for development only.
        if (! app()->isProduction()) {
            $this->call(SampleContentSeeder::class);
        }
    }
}
