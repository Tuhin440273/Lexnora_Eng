<?php

namespace App\Modules\Learning\Services;

use App\Models\User;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\ProgressStatus;
use App\Modules\Learning\Models\UserCourseProgress;
use App\Modules\Learning\Models\UserLessonProgress;

class LessonProgressService
{
    public function started(User $user, Lesson $lesson, PracticeMode $mode): void
    {
        $progress = UserLessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['status' => ProgressStatus::InProgress]
        );

        $progress->update(['last_mode' => $mode, 'last_practiced_at' => now()]);

        $courseId = $lesson->chapter->course_id;
        $course = UserCourseProgress::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $courseId],
            ['status' => ProgressStatus::InProgress, 'started_at' => now()]
        );
        $course->update(['last_accessed_at' => now()]);
    }

    /**
     * Returns true when this run completed the lesson for the first time.
     */
    public function finished(User $user, Lesson $lesson, PracticeMode $mode, float $averageAccuracy): bool
    {
        $progress = UserLessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['status' => ProgressStatus::InProgress]
        );

        $firstTime = $progress->status !== ProgressStatus::Completed;

        $progress->update([
            'status' => ProgressStatus::Completed,
            'last_mode' => $mode,
            'best_accuracy' => max((float) $progress->best_accuracy, $averageAccuracy),
            'sessions_count' => $progress->sessions_count + 1,
            'last_practiced_at' => now(),
            'completed_at' => $progress->completed_at ?? now(),
        ]);

        $this->refreshCourse($user, $lesson);

        return $firstTime;
    }

    private function refreshCourse(User $user, Lesson $lesson): void
    {
        $courseId = $lesson->chapter->course_id;

        $lessonIds = Lesson::learnerVisible()
            ->whereHas('chapter', fn ($chapter) => $chapter->where('course_id', $courseId))
            ->pluck('lessons.id');

        $done = UserLessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', ProgressStatus::Completed->value)
            ->count();

        if ($lessonIds->isNotEmpty() && $done >= $lessonIds->count()) {
            UserCourseProgress::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                ['status' => ProgressStatus::Completed, 'completed_at' => now(), 'last_accessed_at' => now()]
            );
        }
    }
}
