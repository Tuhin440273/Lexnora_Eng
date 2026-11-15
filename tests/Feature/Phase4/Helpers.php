<?php

require_once __DIR__.'/../Phase3/Helpers.php';

use App\Models\User;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\PracticeSession;

/**
 * A published course > chapter > lesson with sentences "Sentence number 1." ...
 */
function publishedLesson(int $sentences = 2): Lesson
{
    $lesson = lessonTree(true);

    foreach (range(1, $sentences) as $position) {
        Sentence::factory()->for($lesson)->create([
            'english_text' => "Sentence number {$position}.",
            'bengali_text' => "বাক্য {$position}",
            'sort_order' => $position,
        ]);
    }

    return $lesson;
}

function learner(): User
{
    return userWithRole('learner');
}

function startPractice(User $user, Lesson $lesson, string $mode = 'hard'): PracticeSession
{
    test()->actingAs($user)->post(route('practice.start', $lesson), ['mode' => $mode]);

    return PracticeSession::where('user_id', $user->id)->latest('id')->firstOrFail();
}

function typeAnswer(User $user, PracticeSession $session, string $text, array $extra = [])
{
    return test()->actingAs($user)->post(route('practice.answer', $session), array_merge(['answer' => $text], $extra));
}
