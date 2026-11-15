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

/**
 * A published lesson whose first sentence has a word and two phrases.
 *
 * @return array{0: Lesson, 1: array<string, Sentence>}
 */
function lessonWithChunks(): array
{
    $lesson = lessonTree(true);
    $first = Sentence::factory()->for($lesson)->create(['english_text' => 'I would like a cup of tea.', 'sort_order' => 1]);
    $second = Sentence::factory()->for($lesson)->create(['english_text' => 'Thank you very much.', 'sort_order' => 2]);

    $word = Sentence::factory()->word()->for($lesson)->create(['english_text' => 'tea', 'parent_id' => $first->id, 'sort_order' => 3]);
    $long = Sentence::factory()->phrase()->for($lesson)->create(['english_text' => 'a cup of tea', 'parent_id' => $first->id, 'sort_order' => 1]);
    $short = Sentence::factory()->phrase()->for($lesson)->create(['english_text' => 'a cup', 'parent_id' => $first->id, 'sort_order' => 2]);

    return [$lesson, compact('first', 'second', 'word', 'long', 'short')];
}
