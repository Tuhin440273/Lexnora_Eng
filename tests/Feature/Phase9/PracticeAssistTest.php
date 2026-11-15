<?php

use App\Models\User;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Learning\Models\AppSetting;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function practiceProps(User $user, PracticeSession $session): array
{
    $props = [];

    test()->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(function (Assert $page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props;
}

it('shows the Bengali meaning with the item the learner is listening to', function () {
    $user = learner();

    $props = practiceProps($user, startPractice($user, publishedLesson(2), 'hard'));

    expect($props['item']['bengali'])->toBe('বাক্য 1')
        ->and(json_encode($props, JSON_UNESCAPED_UNICODE))->not->toContain('বাক্য 2');
});

it('also shows the meaning of a word or phrase', function () {
    [$lesson, $parts] = lessonWithChunks();
    $parts['word']->update(['bengali_text' => 'চা']);
    $user = learner();

    $props = practiceProps($user, startPractice($user, $lesson, 'easy'));   // starts with the word "tea"

    expect($props['item']['id'])->toBe($parts['word']->id)->and($props['item']['bengali'])->toBe('চা');
});

it('sends guide text only to the modes that allow it', function (string $mode, bool $expected) {
    $lesson = publishedLesson(2);
    $user = learner();

    $props = practiceProps($user, startPractice($user, $lesson, $mode));

    expect($props['item']['guide_text'])->toBe($expected ? 'Sentence number 1.' : null)
        ->and(json_encode($props))->not->toContain('Sentence number 2');   // never the later items
})->with([
    'easy is on by default' => ['easy', true],
    'medium is on by default' => ['medium', true],
    'hard is off by default' => ['hard', false],
]);

it('lets the admin switch the guide text on or off per mode', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    AppSetting::set('practice.guide_text.easy', false);
    AppSetting::set('practice.guide_text.hard', true);

    expect(practiceProps($user, startPractice($user, $lesson, 'easy'))['item']['guide_text'])->toBeNull()
        ->and(practiceProps($user, startPractice($user, $lesson, 'hard'))['item']['guide_text'])->toBe('Sentence number 1.');
});

it('does not show the guide in mistake sessions', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    typeAnswer($user, startPractice($user, $lesson), 'wrong');
    $this->actingAs($user)->post(route('mistakes.practice-all'));
    $session = PracticeSession::where('kind', 'mistakes')->firstOrFail();

    expect(practiceProps($user, $session)['item']['guide_text'])->toBeNull();
});

it('keeps the answer out of the page when the guide is off and there is a recording', function () {
    AppSetting::set('practice.guide_text.easy', false);
    $lesson = publishedLesson(1);
    audioFor($lesson->sentences()->firstOrFail());
    $user = learner();

    $props = practiceProps($user, startPractice($user, $lesson, 'easy'));

    expect(json_encode($props))->not->toContain('Sentence number 1');
});

it('plays audio automatically unless the learner turned that off', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    $session = startPractice($user, $lesson);

    expect(practiceProps($user, $session)['autoplay'])->toBeTrue();

    $user->ensureProfile()->update(['preferences' => ['autoplay' => false]]);

    expect(practiceProps($user->fresh(), $session)['autoplay'])->toBeFalse();
});

it('tells the lesson page which modes have the guide', function () {
    $lesson = publishedLesson(1);

    $this->actingAs(learner())->get(route('lessons.show', $lesson))->assertInertia(fn (Assert $page) => $page
        ->where('modes.0.guide', true)->where('modes.1.guide', true)->where('modes.2.guide', false));
});

it('offers the guide settings to admins', function () {
    $this->actingAs(userWithRole('admin'))->get(route('admin.settings.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('fields', fn ($fields) => collect($fields)->pluck('key')->contains('practice.guide_text.hard')));
});
