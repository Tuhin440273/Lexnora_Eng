<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Models\DailyLearningStatistic;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\SessionStatus;
use App\Modules\Learning\Support\UserClock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = UserClock::today($user);
        $from = $today->subDays(29);

        $rows = DailyLearningStatistic::where('user_id', $user->id)
            ->where('stat_date', '>=', $from->toDateString())->get()
            ->keyBy(fn ($r) => $r->stat_date->toDateString());

        $days = collect(range(0, 29))->map(function (int $i) use ($from, $rows) {
            $day = $from->addDays($i);
            $row = $rows[$day->toDateString()] ?? null;

            return [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'minutes' => (int) floor(($row->study_seconds ?? 0) / 60),
                'sentences' => (int) ($row->attempts_count ?? 0),
                'accuracy' => $row?->averageAccuracy(),
            ];
        })->all();

        $byMode = collect(PracticeMode::cases())->map(fn (PracticeMode $mode) => [
            'mode' => $mode->label(),
            'sessions' => PracticeSession::where('user_id', $user->id)->where('mode', $mode->value)
                ->where('status', SessionStatus::Completed->value)->count(),
            'accuracy' => round((float) PracticeSession::where('user_id', $user->id)->where('mode', $mode->value)
                ->where('status', SessionStatus::Completed->value)->avg('average_accuracy'), 1),
        ])->all();

        $hardest = MistakeRecord::where('user_id', $user->id)->with('sentence:id,english_text')
            ->whereHas('sentence')->orderByDesc('times_missed')->limit(5)->get()
            ->map(fn (MistakeRecord $m) => ['text' => $m->sentence->english_text, 'times_missed' => $m->times_missed]);

        return Inertia::render('analytics', ['days' => $days, 'by_mode' => $byMode, 'hardest' => $hardest]);
    }
}
