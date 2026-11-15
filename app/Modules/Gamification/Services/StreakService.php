<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Learning\Support\UserClock;
use Illuminate\Support\Facades\DB;

class StreakService
{
    /**
     * Days in a row (ending today, or yesterday if today has no activity yet)
     * on which the learner practised or checked in.
     */
    public function current(User $user): int
    {
        $days = $this->activeDays($user);
        $today = UserClock::today($user);

        $cursor = isset($days[$today->toDateString()]) ? $today : $today->subDay();
        $streak = 0;

        while (isset($days[$cursor->toDateString()])) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * @return array<string, true>
     */
    public function activeDays(User $user): array
    {
        $practice = DB::table('daily_learning_statistics')
            ->where('user_id', $user->id)->where('attempts_count', '>', 0)->pluck('stat_date');
        $checkins = DB::table('learning_checkins')->where('user_id', $user->id)->pluck('checkin_date');

        $days = [];
        foreach ($practice->merge($checkins) as $date) {
            $days[substr((string) $date, 0, 10)] = true;
        }

        return $days;
    }
}
