<?php

namespace App\Modules\Review\Services;

use App\Models\User;
use App\Modules\Dictation\Models\PracticeAttempt;
use App\Modules\Learning\Enums\ReviewStatus;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Review\Models\ReviewHistory;
use App\Modules\Review\Models\ReviewSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A simple, configurable schedule: `review.intervals_days` lists the days to
 * wait after each successful review. A failed review starts again at step 0.
 * interval_days / repetitions / ease_factor are stored so real spaced
 * repetition can replace this later without a migration.
 */
class ReviewScheduleService
{
    /**
     * @return array<int, int>
     */
    public function intervals(): array
    {
        $intervals = array_values(array_filter(
            array_map('intval', (array) AppSetting::get('review.intervals_days', [1, 3, 7])),
            fn (int $days) => $days > 0,
        ));

        return $intervals === [] ? [1] : $intervals;
    }

    /**
     * Puts an item on the schedule if it is not there yet.
     */
    public function ensureScheduled(User $user, Model $item): ReviewSchedule
    {
        $existing = ReviewSchedule::where('user_id', $user->id)
            ->where('reviewable_type', $item->getMorphClass())
            ->where('reviewable_id', $item->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        $days = $this->intervals()[0];

        return ReviewSchedule::create([
            'user_id' => $user->id,
            'reviewable_type' => $item->getMorphClass(),
            'reviewable_id' => $item->getKey(),
            'status' => ReviewStatus::Active,
            'step' => 0,
            'interval_days' => $days,
            'repetitions' => 0,
            'due_at' => now()->addDays($days),
        ]);
    }

    public function recordResult(ReviewSchedule $schedule, bool $success, ?float $accuracy = null, ?PracticeAttempt $attempt = null): void
    {
        DB::transaction(function () use ($schedule, $success, $accuracy, $attempt): void {
            $intervals = $this->intervals();
            $previousStep = $schedule->step;
            $wasDueAt = $schedule->due_at;

            if ($success) {
                $isLast = $previousStep >= count($intervals) - 1;
                $newStep = $isLast ? $previousStep : $previousStep + 1;
                $days = $intervals[$newStep];

                $schedule->fill([
                    'step' => $newStep,
                    'interval_days' => $days,
                    'repetitions' => $schedule->repetitions + 1,
                    'due_at' => now()->addDays($days),
                    'status' => $isLast ? ReviewStatus::Mastered : ($schedule->status ?? ReviewStatus::Active),
                ]);
            } else {
                $newStep = 0;
                $schedule->fill([
                    'step' => 0,
                    'interval_days' => $intervals[0],
                    'due_at' => now()->addDays($intervals[0]),
                    'status' => ReviewStatus::Active,
                ]);
            }

            $schedule->last_reviewed_at = now();
            $schedule->save();

            ReviewHistory::create([
                'review_schedule_id' => $schedule->id,
                'user_id' => $schedule->user_id,
                'practice_attempt_id' => $attempt?->id,
                'is_successful' => $success,
                'accuracy' => $accuracy,
                'previous_step' => $previousStep,
                'new_step' => $newStep,
                'was_due_at' => $wasDueAt,
                'reviewed_at' => now(),
            ]);
        });
    }
}
