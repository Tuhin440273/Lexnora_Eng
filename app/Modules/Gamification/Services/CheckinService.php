<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Models\LearningCheckin;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Support\UserClock;
use Illuminate\Database\UniqueConstraintViolationException;

class CheckinService
{
    public function __construct(
        private readonly StreakService $streaks,
        private readonly XpService $xp,
        private readonly BadgeService $badges,
    ) {}

    /**
     * One check-in per learner per day (in the learner's timezone).
     *
     * @return array{checkin: LearningCheckin, created: bool}
     */
    public function checkIn(User $user): array
    {
        $today = UserClock::today($user);
        $existing = LearningCheckin::where('user_id', $user->id)->whereDate('checkin_date', $today->toDateString())->first();

        if ($existing) {
            return ['checkin' => $existing, 'created' => false];
        }

        $days = $this->streaks->activeDays($user);
        $yesterdayActive = isset($days[$today->subDay()->toDateString()]);
        $streak = ($yesterdayActive ? $this->streaks->current($user) : 0) + 1;

        $bonus = min(
            (int) AppSetting::get('xp.streak_bonus_per_day', 1) * ($streak - 1),
            (int) AppSetting::get('xp.streak_bonus_max', 10),
        );
        $amount = (int) AppSetting::get('xp.daily_checkin', 5) + $bonus;

        try {
            $checkin = LearningCheckin::create([
                'user_id' => $user->id,
                'checkin_date' => $today->toDateString(),
                'streak_after' => $streak,
                'xp_awarded' => $amount,
            ]);
        } catch (UniqueConstraintViolationException) {
            return [
                'checkin' => LearningCheckin::where('user_id', $user->id)->whereDate('checkin_date', $today->toDateString())->firstOrFail(),
                'created' => false,
            ];
        }

        $this->xp->award($user, $amount, 'daily_checkin', "checkin:{$user->id}:{$today->toDateString()}", 'learning_checkin', $checkin->id);
        $this->badges->check($user);

        return ['checkin' => $checkin, 'created' => true];
    }
}
