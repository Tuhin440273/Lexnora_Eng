<?php

namespace App\Modules\Analytics\Services;

use App\Models\User;
use App\Modules\Analytics\Models\DailyLearningStatistic;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Models\LearningCheckin;
use App\Modules\Gamification\Services\StreakService;
use App\Modules\Gamification\Services\XpService;
use App\Modules\Learning\Enums\ProgressStatus;
use App\Modules\Learning\Enums\SessionStatus;
use App\Modules\Learning\Models\UserLessonProgress;
use App\Modules\Learning\Support\UserClock;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Vocabulary\Models\SavedVocabulary;

class DashboardService
{
    public function __construct(
        private readonly StreakService $streaks,
        private readonly XpService $xp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $totals = DailyLearningStatistic::where('user_id', $user->id)
            ->selectRaw('coalesce(sum(study_seconds),0) as seconds, coalesce(sum(attempts_count),0) as attempts, coalesce(sum(accuracy_total),0) as accuracy')
            ->first();

        $today = UserClock::today($user);
        $todayStat = DailyLearningStatistic::where('user_id', $user->id)->whereDate('stat_date', $today->toDateString())->first();
        $profile = $user->ensureProfile();

        $inProgress = PracticeSession::where('user_id', $user->id)->where('status', SessionStatus::InProgress->value)
            ->with('lesson:id,title')->latest('last_activity_at')->first();

        return [
            'stats' => [
                'study_seconds' => (int) $totals->seconds,
                'completed_lessons' => UserLessonProgress::where('user_id', $user->id)->where('status', ProgressStatus::Completed->value)->count(),
                'sessions' => PracticeSession::where('user_id', $user->id)->where('status', SessionStatus::Completed->value)->count(),
                'average_accuracy' => $totals->attempts > 0 ? round((float) $totals->accuracy / (int) $totals->attempts, 1) : null,
                'streak' => $this->streaks->current($user),
                'vocabulary_learned' => SavedVocabulary::where('user_id', $user->id)->learned()->count(),
                'review_due' => ReviewSchedule::where('user_id', $user->id)->due()->count(),
                'xp' => $this->xp->total($user),
            ],
            'today' => [
                'minutes' => (int) floor(($todayStat->study_seconds ?? 0) / 60),
                'sentences' => (int) ($todayStat->attempts_count ?? 0),
                'goal_minutes' => $profile->daily_goal_minutes,
                'goal_sentences' => $profile->daily_goal_sentences,
                'checked_in' => LearningCheckin::where('user_id', $user->id)->whereDate('checkin_date', $today->toDateString())->exists(),
            ],
            'week' => $this->week($user, $today),
            'recent' => PracticeSession::where('user_id', $user->id)->where('status', SessionStatus::Completed->value)
                ->with('lesson:id,title')->latest('completed_at')->limit(5)->get()
                ->map(fn (PracticeSession $s) => [
                    'id' => $s->id,
                    'title' => $s->lesson?->title ?? ($s->kind === PracticeSession::KIND_REVIEW ? __('Review') : __('Practice again')),
                    'mode' => $s->mode->value,
                    'accuracy' => (float) $s->average_accuracy,
                    'correct' => $s->correct_items,
                    'total' => $s->total_items,
                    'completed_at' => $s->completed_at?->toIso8601String(),
                ]),
            'continue' => $inProgress ? [
                'id' => $inProgress->id,
                'title' => $inProgress->lesson?->title ?? __('Practice'),
                'done' => $inProgress->completed_items,
                'total' => $inProgress->total_items,
            ] : null,
            'badges' => $user->badges()->orderByDesc('user_badges.awarded_at')->get()->map(fn ($b) => [
                'name' => $b->name, 'description' => $b->description, 'icon' => $b->icon,
            ]),
        ];
    }

    /**
     * Last 7 days in the learner's timezone, oldest first (missing days are zero).
     *
     * @return array<int, array{date: string, label: string, minutes: int, sentences: int, xp: int}>
     */
    private function week(User $user, \Carbon\CarbonImmutable $today): array
    {
        $from = $today->subDays(6);
        $rows = DailyLearningStatistic::where('user_id', $user->id)
            ->where('stat_date', '>=', $from->toDateString())->get()
            ->keyBy(fn ($r) => $r->stat_date->toDateString());

        return collect(range(0, 6))->map(function (int $i) use ($from, $rows) {
            $day = $from->addDays($i);
            $row = $rows[$day->toDateString()] ?? null;

            return [
                'date' => $day->toDateString(),
                'label' => $day->format('D'),
                'minutes' => (int) floor(($row->study_seconds ?? 0) / 60),
                'sentences' => (int) ($row->attempts_count ?? 0),
                'xp' => (int) ($row->xp_earned ?? 0),
            ];
        })->all();
    }
}
