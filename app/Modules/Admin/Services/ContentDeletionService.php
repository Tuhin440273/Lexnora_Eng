<?php

namespace App\Modules\Admin\Services;

use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes a piece of content together with everything below it, so no
 * orphaned rows keep showing up in counts and reports. Nothing is erased
 * permanently and audio files stay on disk.
 */
class ContentDeletionService
{
    public function deleteCourse(Course $course): void
    {
        DB::transaction(function () use ($course): void {
            $chapterIds = Chapter::where('course_id', $course->id)->pluck('id');

            $this->deleteLessonTree(Lesson::whereIn('chapter_id', $chapterIds)->pluck('id')->all());

            Chapter::whereIn('id', $chapterIds)->delete();
            $course->delete();
        });
    }

    public function deleteChapter(Chapter $chapter): void
    {
        DB::transaction(function () use ($chapter): void {
            $this->deleteLessonTree(Lesson::where('chapter_id', $chapter->id)->pluck('id')->all());

            $chapter->delete();
        });
    }

    public function deleteLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson): void {
            $this->deleteLessonTree([$lesson->id]);
        });
    }

    public function deleteSentence(Sentence $sentence): void
    {
        DB::transaction(function () use ($sentence): void {
            Sentence::where('parent_id', $sentence->id)->delete();
            $sentence->delete();
        });
    }

    /**
     * @param  array<int, string>  $lessonIds
     */
    private function deleteLessonTree(array $lessonIds): void
    {
        if ($lessonIds === []) {
            return;
        }

        Sentence::whereIn('lesson_id', $lessonIds)->delete();
        Lesson::whereIn('id', $lessonIds)->delete();
    }
}
