<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Learning\Enums\ProgressStatus;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Platform-wide numbers for staff. Everything is aggregated: no learner
     * names, emails or answers are shown here.
     */
    public function __invoke(): Response
    {
        $today = now()->startOfDay();
        $from30 = $today->subDays(29)->toDateString();

        $activeSince = fn (int $days) => (int) DB::table('daily_learning_statistics')
            ->where('attempts_count', '>', 0)
            ->where('stat_date', '>=', $today->subDays($days - 1)->toDateString())
            ->distinct()->count('user_id');

        $totals = DB::table('daily_learning_statistics')
            ->selectRaw('coalesce(sum(attempts_count),0) as attempts, coalesce(sum(accuracy_total),0) as accuracy, coalesce(sum(study_seconds),0) as seconds, coalesce(sum(sessions_count),0) as sessions, coalesce(sum(xp_earned),0) as xp')
            ->first();

        $perDay = DB::table('daily_learning_statistics')
            ->where('stat_date', '>=', $from30)
            ->groupBy('stat_date')
            ->selectRaw('stat_date, count(distinct case when attempts_count > 0 then user_id end) as users, sum(attempts_count) as attempts')
            ->get()->keyBy(fn ($row) => substr((string) $row->stat_date, 0, 10));

        $days = collect(range(0, 29))->map(function (int $i) use ($today, $perDay) {
            $day = $today->subDays(29 - $i);
            $row = $perDay[$day->toDateString()] ?? null;

            return [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'users' => (int) ($row->users ?? 0),
                'attempts' => (int) ($row->attempts ?? 0),
            ];
        })->all();

        $progress = DB::table('user_course_progress')
            ->groupBy('course_id')
            ->selectRaw('course_id, count(*) as started, sum(case when status = ? then 1 else 0 end) as completed', [ProgressStatus::Completed->value])
            ->orderByDesc('started')->limit(10)->get();
        $titles = Course::whereIn('id', $progress->pluck('course_id'))->pluck('title', 'id');

        $hardest = MistakeRecord::query()
            ->groupBy('sentence_id')
            ->selectRaw('sentence_id, sum(times_missed) as missed, count(*) as learners')
            ->orderByDesc('missed')->limit(8)->get();
        $sentences = Sentence::with('lesson:id,title')->whereIn('id', $hardest->pluck('sentence_id'))->get()->keyBy('id');

        return Inertia::render('admin/analytics', [
            'totals' => [
                'learners' => User::role('learner')->count(),
                'new_this_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'active_7' => $activeSince(7),
                'active_30' => $activeSince(30),
                'sessions' => (int) $totals->sessions,
                'attempts' => (int) $totals->attempts,
                'study_hours' => round((int) $totals->seconds / 3600, 1),
                'average_accuracy' => $totals->attempts > 0 ? round((float) $totals->accuracy / (int) $totals->attempts, 1) : null,
                'xp' => (int) $totals->xp,
            ],
            'days' => $days,
            'courses' => $progress->map(fn ($row) => [
                'title' => $titles[$row->course_id] ?? '-',
                'started' => (int) $row->started,
                'completed' => (int) $row->completed,
                'rate' => $row->started > 0 ? (int) round($row->completed / $row->started * 100) : 0,
            ])->values(),
            'hardest' => $hardest->map(fn ($row) => [
                'text' => $sentences[$row->sentence_id]->english_text ?? '-',
                'lesson' => $sentences[$row->sentence_id]->lesson?->title,
                'missed' => (int) $row->missed,
                'learners' => (int) $row->learners,
            ])->values(),
            'content' => [
                'missing_audio' => Sentence::whereDoesntHave('audio')->count(),
                'draft_courses' => Course::where('is_published', false)->count(),
            ],
        ]);
    }
}
