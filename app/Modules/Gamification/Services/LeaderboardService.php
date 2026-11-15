<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Learning\Models\AppSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaderboardService
{
    public const PERIODS = ['daily', 'weekly', 'monthly', 'all'];

    /**
     * The ranking shows first names or chosen display names only, never emails
     * or ids. Learners who switched on "anonymous" appear as "Anonymous
     * learner" (but always see themselves as "You").
     *
     * @return array{metric: string, period: string, entries: array<int, array<string, mixed>>, me: array{rank: int|null, value: int}}
     */
    public function board(User $viewer, string $period): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : 'weekly';
        $metric = AppSetting::get('leaderboard.metric', 'xp') === 'study_time' ? 'study_time' : 'xp';
        $limit = max(1, min(100, (int) AppSetting::get('leaderboard.size', 50)));

        $rows = $this->totals($metric, $period)->orderByDesc('value')->orderBy('user_id')->limit($limit)->get();
        $users = User::with('profile')->whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        $entries = $rows->values()->map(function ($row, int $index) use ($users, $viewer) {
            $user = $users[$row->user_id] ?? null;
            $isMe = (int) $row->user_id === $viewer->id;

            return [
                'rank' => $index + 1,
                'name' => $isMe ? __('You') : $this->displayName($user),
                'value' => (int) $row->value,
                'is_me' => $isMe,
            ];
        })->all();

        $mine = (int) optional($this->totals($metric, $period)->where('user_id', $viewer->id)->first())->value;
        $rank = $mine > 0
            ? DB::query()->fromSub($this->totals($metric, $period)->havingRaw('sum('.$this->column($metric).') > ?', [$mine]), 'better')->count() + 1
            : null;

        return ['metric' => $metric, 'period' => $period, 'entries' => $entries, 'me' => ['rank' => $rank, 'value' => $mine]];
    }

    private function column(string $metric): string
    {
        return $metric === 'xp' ? 'amount' : 'study_seconds';
    }

    private function totals(string $metric, string $period): Builder|\Illuminate\Database\Query\Builder
    {
        $start = match ($period) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => null,
        };

        if ($metric === 'xp') {
            return UserXpTransaction::query()->toBase()
                ->selectRaw('user_id, sum(amount) as value')
                ->from('user_xp_transactions')
                ->when($start, fn ($q) => $q->where('awarded_at', '>=', $start))
                ->groupBy('user_id');
        }

        return DB::table('daily_learning_statistics')
            ->selectRaw('user_id, sum(study_seconds) as value')
            ->when($start, fn ($q) => $q->where('stat_date', '>=', $start->toDateString()))
            ->groupBy('user_id');
    }

    private function displayName(?User $user): string
    {
        if ($user === null || $user->profile?->leaderboard_anonymous) {
            return __('Anonymous learner');
        }

        return $user->profile?->display_name ?: Str::before($user->name, ' ');
    }
}
