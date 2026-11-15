<?php

use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Models\UserNote;
use App\Modules\Review\Models\ReviewHistory;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Review\Services\ReviewScheduleService;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use App\Modules\Vocabulary\Models\Vocabulary;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

/* ---------------- course catalog ---------------- */

it('lists only published courses to learners', function () {
    $lesson = publishedLesson();
    $draft = lessonTree(false);

    $this->actingAs(learner())->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('courses/index')
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $lesson->chapter->course_id)
            ->where('courses.data.0.lessons_count', 1));
});

it('filters the catalog', function () {
    publishedLesson()->chapter->course->update(['title' => 'Cooking Basics', 'cefr_level' => 'A1']);
    publishedLesson()->chapter->course->update(['title' => 'Business Talk', 'cefr_level' => 'B2']);
    $this->actingAs(learner());

    $this->get(route('courses.index', ['search' => 'Cooking']))->assertInertia(fn (Assert $page) => $page->has('courses.data', 1));
    $this->get(route('courses.index', ['cefr_level' => 'B2']))->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)->where('courses.data.0.title', 'Business Talk'));
});

it('shows learner progress on the catalog and course page', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    typeAnswer($user, startPractice($user, $lesson), 'sentence number 1');
    $course = $lesson->chapter->course;

    $this->get(route('courses.index'))->assertInertia(fn (Assert $page) => $page
        ->where('courses.data.0.completed_lessons', 1)->where('courses.data.0.progress', 100));

    $this->get(route('courses.show', $course))->assertInertia(fn (Assert $page) => $page
        ->component('courses/show')
        ->where('course.chapters.0.lessons.0.status', 'completed')
        ->where('course.chapters.0.lessons.0.best_accuracy', 100));
});

it('hides draft courses, chapters and lessons from learners', function () {
    $draftCourse = lessonTree(false)->chapter->course;
    $lesson = publishedLesson();
    $lesson->chapter->course->chapters()->create(['title' => 'Hidden chapter', 'is_published' => false]);
    $lesson->chapter->lessons()->create(['title' => 'Hidden lesson', 'difficulty' => 1, 'estimated_minutes' => 5, 'is_published' => false]);
    $this->actingAs(learner());

    $this->get(route('courses.show', $draftCourse))->assertNotFound();
    $this->get(route('courses.show', $lesson->chapter->course))
        ->assertInertia(fn (Assert $page) => $page->has('course.chapters', 1)->has('course.chapters.0.lessons', 1));
});

it('lets staff preview drafts', function () {
    $draft = lessonTree(false);
    Sentence::factory()->for($draft)->create();
    $this->actingAs(userWithRole('content-manager'));

    $this->get(route('courses.show', $draft->chapter->course))->assertOk();
    $this->get(route('lessons.show', $draft))->assertOk();
});

it('shows the lesson page with the three modes', function () {
    [$lesson, $parts] = lessonWithChunks();
    $user = learner();
    startPractice($user, $lesson, 'easy');

    $this->get(route('lessons.show', $lesson))->assertInertia(fn (Assert $page) => $page
        ->component('lessons/show')
        ->has('modes', 3)
        ->where('modes.0.value', 'easy')->where('modes.0.items', 5)->where('modes.0.hints', 3)->where('modes.0.resume.done', 0)
        ->where('modes.1.items', 4)
        ->where('modes.2.items', 2)->where('modes.2.resume', null));
});

it('answers 404 for a draft lesson page', function () {
    $draft = lessonTree(false);

    $this->actingAs(learner())->get(route('lessons.show', $draft))->assertNotFound();
});

/* ---------------- vocabulary notebook ---------------- */

it('saves a word with its sentence as context and schedules a review', function () {
    $lesson = publishedLesson(1);
    $sentence = $lesson->sentences()->first();
    Vocabulary::factory()->create(['term' => 'Sentence', 'meaning_en' => 'a group of words']);
    $user = learner();

    $this->actingAs($user)->post(route('vocabulary.store'), [
        'term' => '  Sentence ', 'context_sentence_id' => $sentence->id, 'personal_meaning' => 'বাক্য',
    ])->assertSessionHasNoErrors();

    $saved = SavedVocabulary::firstOrFail();

    expect($saved->term)->toBe('Sentence')
        ->and($saved->normalized_term)->toBe('sentence')
        ->and($saved->type)->toBe('word')
        ->and($saved->context_text)->toBe('Sentence number 1.')
        ->and($saved->context_sentence_id)->toBe($sentence->id)
        ->and($saved->personal_meaning)->toBe('বাক্য')
        ->and($saved->vocabulary_id)->not->toBeNull()
        ->and(ReviewSchedule::where('reviewable_id', $saved->id)->exists())->toBeTrue();
});

it('detects phrases and does not save the same term twice', function () {
    $user = learner();
    $this->actingAs($user);

    $this->post(route('vocabulary.store'), ['term' => 'a cup of tea']);
    $this->post(route('vocabulary.store'), ['term' => 'A CUP OF TEA', 'notes' => 'common']);

    expect(SavedVocabulary::count())->toBe(1)
        ->and(SavedVocabulary::first()->type)->toBe('phrase')
        ->and(SavedVocabulary::first()->notes)->toBe('common');
});

it('ignores a context sentence the learner may not see', function () {
    $draft = lessonTree(false);
    $secret = Sentence::factory()->for($draft)->create(['english_text' => 'Secret draft text.']);

    $this->actingAs(learner())->post(route('vocabulary.store'), ['term' => 'secret', 'context_sentence_id' => $secret->id]);

    expect(SavedVocabulary::firstOrFail()->context_text)->toBeNull();
});

it('validates what is saved', function () {
    $this->actingAs(learner());

    $this->post(route('vocabulary.store'), ['term' => ''])->assertSessionHasErrors('term');
    $this->post(route('vocabulary.store'), ['term' => 'ok', 'type' => 'sentence'])->assertSessionHasErrors('type');
    expect(SavedVocabulary::count())->toBe(0);
});

it('lists, searches and filters the notebook', function () {
    $user = learner();
    SavedVocabulary::create(['user_id' => $user->id, 'term' => 'coffee', 'is_learned' => true]);
    SavedVocabulary::create(['user_id' => $user->id, 'term' => 'tea', 'personal_meaning' => 'a hot drink']);
    SavedVocabulary::create(['user_id' => learner()->id, 'term' => 'coffee machine']);
    $this->actingAs($user);

    $this->get(route('vocabulary.index'))->assertInertia(fn (Assert $page) => $page
        ->component('vocabulary/index')->has('items.data', 2)->where('totals.all', 2)->where('totals.learned', 1));
    $this->get(route('vocabulary.index', ['search' => 'hot']))->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.term', 'tea'));
    $this->get(route('vocabulary.index', ['status' => 'learned']))->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('items.data.0.term', 'coffee'));
});

it('edits, marks as learned and deletes a saved word', function () {
    $user = learner();
    $saved = SavedVocabulary::create(['user_id' => $user->id, 'term' => 'tea']);
    app(ReviewScheduleService::class)->ensureScheduled($user, $saved);
    $this->actingAs($user);

    $this->patch(route('vocabulary.update', $saved), ['personal_meaning' => 'চা', 'notes' => 'daily'])->assertSessionHasNoErrors();
    expect($saved->fresh()->personal_meaning)->toBe('চা');

    $this->patch(route('vocabulary.learned', $saved));
    expect($saved->fresh()->is_learned)->toBeTrue()->and($saved->fresh()->learned_at)->not->toBeNull();

    $this->patch(route('vocabulary.learned', $saved));
    expect($saved->fresh()->is_learned)->toBeFalse();

    $this->delete(route('vocabulary.destroy', $saved));
    expect(SavedVocabulary::count())->toBe(0)->and(ReviewSchedule::count())->toBe(0);
});

it('keeps saved words, notes, mistakes and reviews private', function () {
    $owner = learner();
    $saved = SavedVocabulary::create(['user_id' => $owner->id, 'term' => 'tea']);
    $note = UserNote::create(['user_id' => $owner->id, 'body' => 'private']);
    $sentence = Sentence::factory()->for(publishedLesson(1))->create();
    $mistake = MistakeRecord::create(['user_id' => $owner->id, 'sentence_id' => $sentence->id, 'last_missed_at' => now()]);
    $schedule = app(ReviewScheduleService::class)->ensureScheduled($owner, $saved);

    $this->actingAs(learner());

    $this->patch(route('vocabulary.update', $saved), ['notes' => 'x'])->assertForbidden();
    $this->patch(route('vocabulary.learned', $saved))->assertForbidden();
    $this->delete(route('vocabulary.destroy', $saved))->assertForbidden();
    $this->patch(route('notes.update', $note), ['body' => 'hacked'])->assertForbidden();
    $this->delete(route('notes.destroy', $note))->assertForbidden();
    $this->post(route('mistakes.practice', $mistake))->assertForbidden();
    $this->patch(route('mistakes.dismiss', $mistake))->assertForbidden();
    $this->post(route('review.rate', $schedule), ['result' => 'remembered'])->assertForbidden();

    expect($saved->fresh()->notes)->toBeNull()
        ->and($note->fresh()->body)->toBe('private');
});

it('does not even let the super-admin touch a learner\'s private records', function () {
    $owner = learner();
    $saved = SavedVocabulary::create(['user_id' => $owner->id, 'term' => 'tea']);
    $session = startPractice($owner, publishedLesson(1));

    $this->actingAs(userWithRole('super-admin'));

    $this->delete(route('vocabulary.destroy', $saved))->assertForbidden();
    $this->get(route('practice.show', $session))->assertForbidden();

    expect(SavedVocabulary::count())->toBe(1);
});

/* ---------------- notes ---------------- */

it('creates, edits, searches and deletes notes', function () {
    $lesson = publishedLesson(1);
    $sentence = $lesson->sentences()->first();
    $user = learner();
    $this->actingAs($user);

    $this->post(route('notes.store'), ['body' => 'Remember the plural.', 'title' => 'Grammar', 'sentence_id' => $sentence->id])->assertSessionHasNoErrors();
    $this->post(route('notes.store'), ['body' => 'A general note.']);

    $note = UserNote::where('title', 'Grammar')->firstOrFail();
    expect($note->sentence_id)->toBe($sentence->id);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->component('notes/index')->has('notes.data', 2));
    $this->get(route('notes.index', ['search' => 'plural']))->assertInertia(fn (Assert $page) => $page->has('notes.data', 1)->where('notes.data.0.sentence', 'Sentence number 1.'));

    $this->patch(route('notes.update', $note), ['body' => 'Changed.', 'title' => null]);
    expect($note->fresh()->body)->toBe('Changed.');

    $this->delete(route('notes.destroy', $note));
    expect(UserNote::count())->toBe(1);
});

it('validates notes and ignores sentences the learner cannot see', function () {
    $secret = Sentence::factory()->for(lessonTree(false))->create();
    $this->actingAs(learner());

    $this->post(route('notes.store'), ['body' => ''])->assertSessionHasErrors('body');
    $this->post(route('notes.store'), ['body' => str_repeat('a', 5001)])->assertSessionHasErrors('body');

    $this->post(route('notes.store'), ['body' => 'hello', 'sentence_id' => $secret->id]);
    expect(UserNote::firstOrFail()->sentence_id)->toBeNull();
});

/* ---------------- mistakes notebook ---------------- */

it('lists open mistakes with the learner\'s answer and the correct one', function () {
    $lesson = publishedLesson(2);
    $user = learner();
    $session = startPractice($user, $lesson);
    typeAnswer($user, $session, 'sentence number one');
    typeAnswer($user, $session, 'sentence number 2');

    $this->get(route('mistakes.index'))->assertInertia(fn (Assert $page) => $page
        ->component('mistakes/index')
        ->where('counts.open', 1)->where('counts.resolved', 0)
        ->has('items.data', 1)
        ->where('items.data.0.expected', 'Sentence number 1.')
        ->where('items.data.0.typed', 'sentence number one')
        ->where('items.data.0.times_missed', 1));
});

it('lets a learner practise a mistake again and resolves it when right', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    typeAnswer($user, startPractice($user, $lesson), 'wrong');
    $mistake = MistakeRecord::firstOrFail();

    $this->post(route('mistakes.practice', $mistake));
    $session = PracticeSession::where('kind', 'mistakes')->firstOrFail();

    expect($session->total_items)->toBe(1)->and($session->lesson_id)->toBeNull();

    typeAnswer($user, $session, 'sentence number 1');

    expect($mistake->fresh()->resolved_at)->not->toBeNull()
        ->and($session->fresh()->status->value)->toBe('completed');

    $this->get(route('practice.summary', $session))->assertInertia(fn (Assert $page) => $page->where('session.title', 'Practice again'));
});

it('practises all open mistakes at once and can dismiss one', function () {
    $lesson = publishedLesson(2);
    $user = learner();
    $session = startPractice($user, $lesson);
    typeAnswer($user, $session, 'bad');
    typeAnswer($user, $session, 'worse');
    $this->actingAs($user);

    $this->post(route('mistakes.practice-all'));
    expect(PracticeSession::where('kind', 'mistakes')->firstOrFail()->total_items)->toBe(2);

    $mistake = MistakeRecord::first();
    $this->patch(route('mistakes.dismiss', $mistake));
    expect($mistake->fresh()->resolved_at)->not->toBeNull();

    $this->get(route('mistakes.index', ['show' => 'resolved']))->assertInertia(fn (Assert $page) => $page->has('items.data', 1)->where('show', 'resolved'));
});

it('says so when there is nothing to practise', function () {
    $this->actingAs(learner())->post(route('mistakes.practice-all'))->assertRedirect();

    expect(PracticeSession::count())->toBe(0);
});

/* ---------------- review ---------------- */

it('moves a sentence through the review steps', function () {
    $sentence = Sentence::factory()->for(publishedLesson(1))->create();
    $user = learner();
    $service = app(ReviewScheduleService::class);
    $schedule = $service->ensureScheduled($user, $sentence);

    expect($schedule->step)->toBe(0)->and($schedule->interval_days)->toBe(1)
        ->and($schedule->due_at->isSameDay(now()->addDay()))->toBeTrue()
        ->and($service->ensureScheduled($user, $sentence)->id)->toBe($schedule->id);

    $service->recordResult($schedule, true, 100);
    expect($schedule->fresh()->step)->toBe(1)->and($schedule->fresh()->interval_days)->toBe(3)->and($schedule->fresh()->repetitions)->toBe(1);

    $service->recordResult($schedule->fresh(), false, 40);
    expect($schedule->fresh()->step)->toBe(0)->and($schedule->fresh()->status->value)->toBe('active');

    foreach (range(1, 6) as $ignored) {
        $service->recordResult($schedule->fresh(), true, 100);
    }
    expect($schedule->fresh()->status->value)->toBe('mastered');

    $history = ReviewHistory::orderBy('id')->get();
    expect($history)->toHaveCount(8)
        ->and($history[0]->previous_step)->toBe(0)->and($history[0]->new_step)->toBe(1)
        ->and($history[1]->is_successful)->toBeFalse()
        ->and($history[1]->previous_step)->toBe(1)->and($history[1]->new_step)->toBe(0);
});

it('follows the review intervals from the admin settings', function () {
    AppSetting::set('review.intervals_days', [2, 5]);
    $sentence = Sentence::factory()->for(publishedLesson(1))->create();

    $schedule = app(ReviewScheduleService::class)->ensureScheduled(learner(), $sentence);

    expect($schedule->interval_days)->toBe(2);
});

it('shows what is due and starts a review session', function () {
    $lesson = publishedLesson(2);
    $user = learner();
    $service = app(ReviewScheduleService::class);
    foreach ($lesson->sentences as $sentence) {
        $service->ensureScheduled($user, $sentence)->update(['due_at' => now()->subHour()]);
    }
    $saved = SavedVocabulary::create(['user_id' => $user->id, 'term' => 'tea', 'personal_meaning' => 'চা']);
    $service->ensureScheduled($user, $saved)->update(['due_at' => now()->subHour()]);
    $this->actingAs($user);

    $this->get(route('review.index'))->assertInertia(fn (Assert $page) => $page
        ->component('review/index')
        ->where('due.sentences', 2)->where('due.vocabulary', 1)
        ->has('vocabulary', 1)->where('vocabulary.0.term', 'tea'));

    $this->post(route('review.start'));
    $session = PracticeSession::where('kind', 'review')->firstOrFail();
    expect($session->total_items)->toBe(2);

    typeAnswer($user, $session, 'sentence number 1');
    typeAnswer($user, $session, 'wrong');

    $schedules = ReviewSchedule::where('reviewable_type', 'sentence')->get();
    expect($schedules->where('step', 1))->toHaveCount(1)
        ->and($schedules->where('step', 0))->toHaveCount(1)
        ->and(ReviewHistory::count())->toBe(2)
        ->and(ReviewSchedule::due()->where('reviewable_type', 'sentence')->count())->toBe(0);
});

it('does not start a review when nothing is due', function () {
    $this->actingAs(learner())->post(route('review.start'))->assertRedirect();

    expect(PracticeSession::count())->toBe(0);
});

it('lets a learner rate a vocabulary review', function () {
    $user = learner();
    $saved = SavedVocabulary::create(['user_id' => $user->id, 'term' => 'tea']);
    $schedule = app(ReviewScheduleService::class)->ensureScheduled($user, $saved);
    $this->actingAs($user);

    $this->post(route('review.rate', $schedule), ['result' => 'remembered']);
    expect($schedule->fresh()->step)->toBe(1);

    $this->post(route('review.rate', $schedule), ['result' => 'forgot']);
    expect($schedule->fresh()->step)->toBe(0);

    $this->post(route('review.rate', $schedule), ['result' => 'maybe'])->assertSessionHasErrors('result');

    $this->get(route('review.index', ['tab' => 'history']))->assertInertia(fn (Assert $page) => $page
        ->where('tab', 'history')->has('history.data', 2)->where('history.data.0.label', 'tea'));
});

it('only rates vocabulary schedules', function () {
    $user = learner();
    $sentence = Sentence::factory()->for(publishedLesson(1))->create();
    $schedule = app(ReviewScheduleService::class)->ensureScheduled($user, $sentence);

    $this->actingAs($user)->post(route('review.rate', $schedule), ['result' => 'remembered'])->assertNotFound();
});
