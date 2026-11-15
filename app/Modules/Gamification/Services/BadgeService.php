<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Models\Badge;
use App\Modules\Learning\Enums\ProgressStatus;
use App\Modules\Learning\Enums\SessionStatus;
use App\Modules\Learning\Models\UserLessonProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    public function __construct(
        private readonly XpService $xp,
        private readonly StreakService $streaks,
    ) {}

    /**
     * Awards every badge whose goal is reached. A badge's XP reward can unlock
     * another badge, so a second pass runs.
     *
     * @return Collection<int, Badge> Badges earned by this call.
     */
    public function check(User $user): Collection
    {
        $earned = collect();

        for ($pass = 0; $pass < 2; $pass++) {
            $owned = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_id')->all();
            $metrics = [];
            $newlyEarned = false;

            foreach (Badge::where('is_active', true)->whereNotIn('id', $owned)->get() as $badge) {
                $metrics[$badge->criteria_type] ??= $this->metric($user, $badge->criteria_type);

                if ($metrics[$badge->criteria_type] < $badge->criteria_value) {
                    continue;
                }

                $inserted = DB::table('user_badges')->insertOrIgnore([
                    'user_id' => $user->id, 'badge_id' => $badge->id, 'awarded_at' => now(),
                ]);

                if ($inserted > 0) {
                    $newlyEarned = true;
                    $earned->push($badge);
                    $this->xp->award($user, $badge->xp_reward, 'badge', "badge:{$badge->id}:{$user->id}", 'badge', $badge->id, $badge->name);
                }
            }

            if (! $newlyEarned) {
                break;
            }
        }

        return $earned;
    }

    private function metric(User $user, string $type): int
    {
        return match ($type) {
            'xp_total' => $this->xp->total($user),
            'streak_days' => $this->streaks->current($user),
            'lessons_completed' => UserLessonProgress::where('user_id', $user->id)
                ->where('status', ProgressStatus::Completed->value)->count(),
            'sessions_completed' => PracticeSession::where('user_id', $user->id)
                ->where('status', SessionStatus::Completed->value)->count(),
            default => 0,
        };
    }
}
