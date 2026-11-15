<?php

namespace Database\Seeders;

use App\Modules\Learning\Models\AppSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /** Inserts missing defaults only, so admin edits survive re-seeding. */
    public function run(): void
    {
        foreach ((array) config('learning.defaults', []) as $key => $value) {
            AppSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => strtok($key, '.')]
            );
        }
    }
}
