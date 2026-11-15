<?php

namespace Database\Seeders;

use App\Modules\Gamification\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['first_session', 'First Steps', 'Finish your first practice session.', 'footprints', 'sessions_completed', 1, 10],
            ['sessions_10', 'Getting Serious', 'Finish 10 practice sessions.', 'repeat', 'sessions_completed', 10, 30],
            ['streak_3', 'Warming Up', 'Study 3 days in a row.', 'flame', 'streak_days', 3, 15],
            ['streak_7', 'On Fire', 'Study 7 days in a row.', 'flame', 'streak_days', 7, 30],
            ['streak_30', 'Unstoppable', 'Study 30 days in a row.', 'medal', 'streak_days', 30, 100],
            ['lessons_5', 'Lesson Explorer', 'Complete 5 lessons.', 'book-open', 'lessons_completed', 5, 20],
            ['lessons_25', 'Bookworm', 'Complete 25 lessons.', 'library', 'lessons_completed', 25, 75],
            ['xp_500', 'Rising Star', 'Earn 500 XP.', 'star', 'xp_total', 500, 25],
            ['xp_5000', 'Champion', 'Earn 5,000 XP.', 'trophy', 'xp_total', 5000, 100],
        ];

        foreach ($badges as [$code, $name, $description, $icon, $type, $value, $xp]) {
            Badge::updateOrCreate(['code' => $code], [
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'criteria_type' => $type,
                'criteria_value' => $value,
                'xp_reward' => $xp,
                'is_active' => true,
            ]);
        }
    }
}
