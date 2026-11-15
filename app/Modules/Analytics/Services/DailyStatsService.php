<?php

namespace App\Modules\Analytics\Services;

use App\Models\User;
use App\Modules\Analytics\Models\DailyLearningStatistic;
use App\Modules\Learning\Support\UserClock;

class DailyStatsService
{
    /**
     * Adds numbers to today's row of the learner (created on first use).
     *
     * @param  array<string, int|float>  $increments  Column => amount.
     */
    public function add(User $user, array $increments): void
    {
        $stat = DailyLearningStatistic::firstOrCreate([
            'user_id' => $user->id,
            'stat_date' => UserClock::today($user)->toDateString(),
        ]);

        $stat->incrementEach($increments);
    }
}
