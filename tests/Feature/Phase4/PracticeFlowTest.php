<?php

use App\Modules\Analytics\Models\DailyLearningStatistic;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeAttempt;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Learning\Enums\ProgressStatus;
use App\Modules\Learning\Models\UserLessonProgress;
use App\Modules\Review\Models\ReviewSchedule;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

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

it('plans easy, medium and hard sessions differently', function () {
    [$lesson, $s] = lessonWithChunks();
    $user = learner();

    expect(startPractice($user, $lesson, 'easy')->plan)
        ->toBe([$s['word']->id, $s['short']->id, $s['long']->id, $s['first']->id, $s['second']->id]);
    expect(startPractice($user, $lesson, 'medium')->plan)
        ->toBe([$s['short']->id, $s['long']->id, $s['first']->id, $s['second']->id]);
    expect(startPractice($user, $lesson, 'hard')->plan)
        ->toBe([$s['first']->id, $s['second']->id]);
});

it('resumes the unfinished session of the same mode', function () {
    $lesson = publishedLesson();
    $user = learner();

    $first = startPractice($user, $lesson, 'hard');
    $again = startPractice($user, $lesson, 'hard');
    $other = startPractice($user, $lesson, 'easy');

    expect($again->id)->toBe($first->id)
        ->and($other->id)->not->toBe($first->id)
        ->and(PracticeSession::count())->toBe(2);
});

it('does not start a session for a lesson without sentences', function () {
    $lesson = lessonTree(true);

    $this->actingAs(learner())->post(route('practice.start', $lesson), ['mode' => 'hard'])->assertRedirect();

    expect(PracticeSession::count())->toBe(0);
});

it('rejects an unknown mode', function () {
    $this->actingAs(learner())
        ->post(route('practice.start', publishedLesson()), ['mode' => 'nightmare'])
        ->assertSessionHasErrors('mode');
});

it('hides draft lessons from learners but not from staff', function () {
    $lesson = lessonTree(false);
    Sentence::factory()->for($lesson)->create();

    $this->actingAs(learner())->post(route('practice.start', $lesson), ['mode' => 'hard'])->assertNotFound();
    $this->actingAs(userWithRole('content-manager'))->post(route('practice.start', $lesson), ['mode' => 'hard'])->assertRedirect();

    expect(PracticeSession::count())->toBe(1);
});

it('sends only the current item and never the answer', function () {
    $lesson = publishedLesson(3);
    $user = learner();
    $session = startPractice($user, $lesson);
    $props = null;

    $this->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(function (Assert $page) use (&$props) {
            $page->component('practice/show')->where('session.position', 1)->where('session.total', 3);
            $props = $page->toArray()['props'];
        });

    $json = json_encode($props);

    expect($json)->not->toContain('Sentence number 2')
        ->and($json)->not->toContain('Sentence number 3')
        ->and($props['item'])->not->toHaveKey('english_text')
        ->and($props['item']['word_count'])->toBe(3);
});

it('only offers the browser voice when there is no recording', function () {
    $lesson = publishedLesson(1);
    $sentence = $lesson->sentences()->first();
    $user = learner();
    $session = startPractice($user, $lesson);

    $this->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page->where('item.audio_url', null)->where('item.speak_text', $sentence->english_text));

    audioFor($sentence);

    $this->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('item.speak_text', null)
            ->where('item.audio_url', fn ($url) => str_contains($url, '/audio/')));
});

it('scores a correct answer and moves to the next sentence', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson);

    typeAnswer($user, $session, 'sentence number 1', ['replay_count' => 2, 'playback_speed' => 0.75, 'time_spent_ms' => 4000])
        ->assertRedirect(route('practice.show', $session));

    $attempt = PracticeAttempt::firstOrFail();

    expect($attempt->is_correct)->toBeTrue()
        ->and((float) $attempt->accuracy)->toBe(100.0)
        ->and($attempt->expected_text)->toBe('Sentence number 1.')
        ->and($attempt->submitted_text)->toBe('sentence number 1')
        ->and($attempt->replay_count)->toBe(2)
        ->and((float) $attempt->playback_speed)->toBe(0.75)
        ->and($attempt->attempt_number)->toBe(1)
        ->and($session->fresh()->completed_items)->toBe(1);

    $this->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.is_correct', true)
            ->where('result.expected', 'Sentence number 1.')
            ->where('result.bengali', 'বাক্য 1')
            ->where('session.position', 2));
});

it('shows the result only once', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson);
    typeAnswer($user, $session, 'sentence number 1');

    $this->get(route('practice.show', $session));

    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page->where('result', null));
});

it('scores a wrong answer and records the mistake', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson);

    typeAnswer($user, $session, 'sentence number one');

    $this->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('result.is_correct', false)
            ->where('result.counts.incorrect', 1)
            ->where('result.accuracy', fn ($accuracy) => $accuracy > 0 && $accuracy < 100));

    $mistake = MistakeRecord::firstOrFail();

    expect($mistake->times_missed)->toBe(1)
        ->and($mistake->resolved_at)->toBeNull()
        ->and($mistake->latestAttempt->submitted_text)->toBe('sentence number one');
});

it('counts repeated mistakes and resolves them when answered correctly', function () {
    $lesson = publishedLesson(1);
    $user = learner();

    typeAnswer($user, startPractice($user, $lesson), 'wrong');
    $mistake = MistakeRecord::firstOrFail();

    typeAnswer($user, startPractice($user, $lesson), 'still wrong');
    expect($mistake->fresh()->times_missed)->toBe(2);

    typeAnswer($user, startPractice($user, $lesson), 'sentence number 1');
    expect($mistake->fresh()->resolved_at)->not->toBeNull();

    typeAnswer($user, startPractice($user, $lesson), 'wrong again');
    expect($mistake->fresh()->resolved_at)->toBeNull()
        ->and($mistake->fresh()->times_missed)->toBe(3);
});

it('completes the session, the lesson and pays the XP once', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson, 'hard');

    typeAnswer($user, $session, 'sentence number 1');
    typeAnswer($user, $session, 'sentence number 2');

    $session->refresh();

    expect($session->status->value)->toBe('completed')
        ->and($session->correct_items)->toBe(2)
        ->and((float) $session->average_accuracy)->toBe(100.0)
        ->and($session->completed_at)->not->toBeNull()
        ->and($session->xp_awarded)->toBe(55)   // 2 x 15 (hard x1.5) + 25 lesson bonus
        ->and(UserXpTransaction::where('user_id', $user->id)->sum('amount'))->toBe(55);

    $progress = UserLessonProgress::firstOrFail();
    expect($progress->status)->toBe(ProgressStatus::Completed)
        ->and((float) $progress->best_accuracy)->toBe(100.0)
        ->and($progress->sessions_count)->toBe(1);

    // the same day, the same lesson again: nothing more to earn
    $again = startPractice($user, $lesson, 'hard');
    typeAnswer($user, $again, 'sentence number 1');
    typeAnswer($user, $again, 'sentence number 2');

    expect(UserXpTransaction::where('user_id', $user->id)->sum('amount'))->toBe(55)
        ->and(UserLessonProgress::firstOrFail()->sessions_count)->toBe(2);
});

it('shows the summary after the last answer', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    $session = startPractice($user, $lesson);

    typeAnswer($user, $session, 'sentence number 1');

    $this->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page->where('result.finished', true)->where('item', null));

    $this->get(route('practice.show', $session))->assertRedirect(route('practice.summary', $session));

    $this->get(route('practice.summary', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->component('practice/summary')
            ->where('session.correct', 1)
            ->where('session.total', 1)
            ->has('attempts', 1)
            ->where('attempts.0.expected', 'Sentence number 1.'));
});

it('refuses answers once the session is finished', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    $session = startPractice($user, $lesson);
    typeAnswer($user, $session, 'sentence number 1');

    typeAnswer($user, $session, 'one more');

    expect(PracticeAttempt::count())->toBe(1);
});

it('validates the answer request', function (array $bad, string $field) {
    $user = learner();
    $session = startPractice($user, publishedLesson());

    typeAnswer($user, $session, 'x', $bad)->assertSessionHasErrors($field);
    expect(PracticeAttempt::count())->toBe(0);
})->with([
    'bad speed' => [['playback_speed' => 7], 'playback_speed'],
    'negative replays' => [['replay_count' => -1], 'replay_count'],
    'huge time' => [['time_spent_ms' => 99999999], 'time_spent_ms'],
]);

it('needs an answer of reasonable length', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson());

    $this->actingAs($user)->post(route('practice.answer', $session), ['answer' => ''])->assertSessionHasErrors('answer');
    $this->post(route('practice.answer', $session), ['answer' => str_repeat('a', 1001)])->assertSessionHasErrors('answer');
});

it('ignores anything the client says about correctness, XP or the sentence', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson);
    $second = $lesson->sentences()->orderBy('sort_order')->get()[1];

    typeAnswer($user, $session, 'totally wrong', [
        'is_correct' => true, 'accuracy' => 100, 'xp' => 9999, 'sentence_id' => $second->id, 'user_id' => 999,
    ]);

    $attempt = PracticeAttempt::firstOrFail();

    expect($attempt->is_correct)->toBeFalse()
        ->and($attempt->sentence_id)->not->toBe($second->id)
        ->and($attempt->user_id)->toBe($user->id)
        ->and((float) $attempt->accuracy)->toBeLessThan(100)
        ->and(UserXpTransaction::sum('amount'))->toBe(2);   // only the "tried" amount
});

it('keeps private sessions private', function () {
    $session = startPractice($owner = learner(), publishedLesson());
    $this->actingAs($intruder = learner());

    $this->get(route('practice.show', $session))->assertForbidden();
    $this->post(route('practice.answer', $session), ['answer' => 'x'])->assertForbidden();
    $this->post(route('practice.hint', $session))->assertForbidden();
    $this->get(route('practice.summary', $session))->assertForbidden();
    expect(PracticeAttempt::count())->toBe(0);
});

it('sends guests to the login page', function () {
    $session = startPractice(learner(), publishedLesson());
    auth()->logout();

    $this->get(route('practice.show', $session))->assertRedirect(route('login'));
});

it('gives hints up to the limit of the mode', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    $session = startPractice($user, $lesson, 'easy');   // 3 hints
    $this->actingAs($user);

    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page
        ->where('hint.allowed', 3)->where('hint.used', 0)->where('hint.text', null));

    $this->post(route('practice.hint', $session));
    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page
        ->where('hint.used', 1)->where('hint.text', 'S_______ n_____ 1.'));

    $this->post(route('practice.hint', $session));
    $this->post(route('practice.hint', $session));
    $this->post(route('practice.hint', $session));   // a fourth is refused

    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page->where('hint.used', 3));

    typeAnswer($user, $session, 'sentence number 1');
    expect(PracticeAttempt::firstOrFail()->hints_used)->toBe(3);
});

it('offers no hints in hard mode', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(), 'hard');

    $this->actingAs($user)->post(route('practice.hint', $session));

    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page
        ->where('hint.allowed', 0)->where('hint.text', null));
});

it('counts at most two minutes of study time per answer', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson());
    $session->update(['last_activity_at' => now()->subHours(5)]);

    typeAnswer($user, $session, 'sentence number 1');

    expect($session->fresh()->duration_seconds)->toBe(120);
});

it('updates the daily statistics', function () {
    $lesson = publishedLesson();
    $user = learner();
    $session = startPractice($user, $lesson);

    typeAnswer($user, $session, 'sentence number 1');
    typeAnswer($user, $session, 'wrong');

    $stat = DailyLearningStatistic::where('user_id', $user->id)->firstOrFail();

    expect($stat->attempts_count)->toBe(2)
        ->and($stat->correct_attempts)->toBe(1)
        ->and($stat->sessions_count)->toBe(1)
        ->and($stat->lessons_completed)->toBe(1)
        ->and($stat->xp_earned)->toBe(15 + 2 + 25)
        ->and($stat->averageAccuracy())->toBeGreaterThan(50);
});

it('schedules full sentences for review, not their chunks', function () {
    [$lesson, $s] = lessonWithChunks();
    $user = learner();
    $session = startPractice($user, $lesson, 'easy');

    foreach (range(1, 5) as $ignored) {
        typeAnswer($user, $session, 'anything');
    }

    $scheduled = ReviewSchedule::where('user_id', $user->id)->pluck('reviewable_id')->all();

    expect($scheduled)->toContain($s['first']->id, $s['second']->id)
        ->and($scheduled)->not->toContain($s['word']->id)
        ->and(ReviewSchedule::first()->reviewable_type)->toBe('sentence');
});

it('skips sentences that were deleted during a session', function () {
    $lesson = publishedLesson(3);
    $user = learner();
    $session = startPractice($user, $lesson);
    $lesson->sentences()->orderBy('sort_order')->first()->delete();

    $this->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(fn (Assert $page) => $page->where('item.id', $lesson->sentences()->orderBy('sort_order')->first()->id));
});
