<?php

namespace App\Modules\Courses\Support;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;

class LessonAccess
{
    /** Learners see published content only; content staff may preview drafts. */
    public static function canViewLesson(User $user, Lesson $lesson): bool
    {
        return $user->canAccessAdmin()
            || Lesson::learnerVisible()->whereKey($lesson->id)->exists();
    }

    public static function canViewCourse(User $user, Course $course): bool
    {
        return $course->is_published || $user->canAccessAdmin();
    }
}
