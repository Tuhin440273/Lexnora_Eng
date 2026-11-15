<?php

use App\Modules\Admin\Support\SettingsSchema;
use App\Modules\Learning\Models\AppSetting;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function settingsPayload(array $overrides = []): array
{
    $values = [];
    foreach (SettingsSchema::forForm() as $field) {
        $values[$field['id']] = $field['value'];
    }

    return ['values' => array_merge($values, $overrides)];
}

it('opens the settings page to staff with the permission', function () {
    $this->actingAs(userWithRole('content-manager'))->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs(learner())->get(route('admin.settings.edit'))->assertForbidden();

    $this->actingAs(userWithRole('admin'))->get(route('admin.settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/settings/index')->has('fields', 20)
            ->where('fields.0.id', 'dictation__correct_threshold')->where('fields.0.value', 100));
});

it('saves typed values and uses them straight away', function () {
    $this->actingAs(userWithRole('admin'));

    $this->put(route('admin.settings.update'), settingsPayload([
        'dictation__correct_threshold' => 80,
        'dictation__ignore_case' => false,
        'practice__hints__easy' => 5,
        'practice__playback_speeds' => '1.5, 0.5, 1, 1',
        'review__intervals_days' => '2, 5, 12',
        'xp__mode_multiplier__hard' => '2.5',
        'leaderboard__metric' => 'study_time',
    ]))->assertSessionHasNoErrors();

    expect(AppSetting::get('dictation.correct_threshold'))->toBe(80)
        ->and(AppSetting::get('dictation.ignore_case'))->toBeFalse()
        ->and(AppSetting::get('practice.hints.easy'))->toBe(5)
        ->and(AppSetting::get('practice.playback_speeds'))->toEqual([0.5, 1, 1.5])   // 1.0 is stored as JSON 1; readers cast to float
        ->and(AppSetting::get('review.intervals_days'))->toBe([2, 5, 12])
        ->and(AppSetting::get('xp.mode_multiplier.hard'))->toBe(2.5)
        ->and(AppSetting::get('leaderboard.metric'))->toBe('study_time');

    $this->get(route('admin.settings.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('fields.7.value', '2, 5, 12'));
});

it('rejects invalid settings and keeps the old values', function (string $field, mixed $bad) {
    $this->actingAs(userWithRole('admin'))
        ->put(route('admin.settings.update'), settingsPayload([$field => $bad]))
        ->assertSessionHasErrors("values.{$field}");

    expect(AppSetting::get('dictation.correct_threshold'))->toBe(100);
})->with([
    ['dictation__correct_threshold', 10],
    ['dictation__correct_threshold', 101],
    ['practice__hints__easy', -1],
    ['xp__per_correct_sentence', 'lots'],
    ['xp__mode_multiplier__easy', 0],
    ['review__intervals_days', '1, two, 3'],
    ['review__intervals_days', ''],
    ['review__intervals_days', '1,2,3,4,5,6,7,8,9,10,11,12,13'],
    ['review__intervals_days', '0, 5'],
    ['practice__playback_speeds', '9'],
    ['leaderboard__metric', 'luck'],
    ['leaderboard__size', 1],
]);

it('requires every setting', function () {
    $payload = settingsPayload();
    unset($payload['values']['xp__daily_checkin']);

    $this->actingAs(userWithRole('admin'))->put(route('admin.settings.update'), $payload)->assertSessionHasErrors('values.xp__daily_checkin');
});

it('lets only the right people change or reset settings', function () {
    $this->actingAs(userWithRole('content-manager'));

    $this->put(route('admin.settings.update'), settingsPayload(['dictation__correct_threshold' => 60]))->assertForbidden();
    $this->delete(route('admin.settings.reset'))->assertForbidden();

    expect(AppSetting::get('dictation.correct_threshold'))->toBe(100);
});

it('goes back to the defaults', function () {
    AppSetting::set('dictation.correct_threshold', 60);
    AppSetting::set('xp.daily_checkin', 99);
    expect(AppSetting::get('xp.daily_checkin'))->toBe(99);

    $this->actingAs(userWithRole('admin'))->delete(route('admin.settings.reset'));

    expect(AppSetting::get('dictation.correct_threshold'))->toBe(100)
        ->and(AppSetting::get('xp.daily_checkin'))->toBe(5)
        ->and(AppSetting::count())->toBe(0);
});

it('changes how answers are scored', function () {
    $lesson = publishedLesson(1);
    $user = learner();
    $this->actingAs(userWithRole('admin'))->put(route('admin.settings.update'), settingsPayload(['dictation__correct_threshold' => 80]));

    typeAnswer($user, startPractice($user, $lesson), 'sentence number one');

    expect(App\Modules\Dictation\Models\PracticeAttempt::firstOrFail()->is_correct)->toBeTrue();
});

it('describes every default setting in the schema', function () {
    $defaults = array_keys(config('learning.defaults'));
    $described = array_column(SettingsSchema::fields(), 'key');

    expect(array_diff($defaults, $described))->toBe([], 'a setting has no admin field');
});

/* ---------------- admin analytics ---------------- */

it('shows platform analytics only to those who may view them', function () {
    $this->actingAs(learner())->get(route('admin.analytics'))->assertForbidden();
    $this->actingAs(userWithRole('content-manager'))->get(route('admin.analytics'))->assertForbidden();
    $this->actingAs(userWithRole('admin'))->get(route('admin.analytics'))->assertOk();
});

it('adds up learning across all learners without exposing anyone', function () {
    $lesson = publishedLesson(2);
    $ann = App\Models\User::factory()->create(['name' => 'Ann Secret', 'email' => 'ann.secret@school.test']);
    $ann->assignRole('learner');
    $bob = learner();

    $one = startPractice($ann, $lesson);
    typeAnswer($ann, $one, 'sentence number 1');
    typeAnswer($ann, $one, 'wrong');
    $two = startPractice($bob, $lesson);
    typeAnswer($bob, $two, 'wrong');
    typeAnswer($bob, $two, 'sentence number 2');

    $props = null;
    $this->actingAs(userWithRole('admin'))->get(route('admin.analytics'))->assertInertia(function (Assert $page) use (&$props) {
        $page->component('admin/analytics')
            ->where('totals.sessions', 2)->where('totals.attempts', 4)->where('totals.active_7', 2)->where('totals.active_30', 2)
            ->has('days', 30)->where('days.29.users', 2)->where('days.29.attempts', 4)
            ->where('courses.0.started', 2)->where('courses.0.completed', 2)->where('courses.0.rate', 100)
            ->where('hardest.0.missed', 1)->where('content.missing_audio', 2);
        $props = $page->toArray()['props'];
    });

    $json = json_encode($props);
    expect($json)->not->toContain('Ann Secret')->not->toContain('ann.secret');
});

it('copes with an empty platform', function () {
    $this->actingAs(userWithRole('admin'))->get(route('admin.analytics'))
        ->assertInertia(fn (Assert $page) => $page->where('totals.attempts', 0)->where('totals.average_accuracy', null)->has('courses', 0)->has('hardest', 0));
});

it('serves the landing page to guests and members', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('welcome'));
    $this->actingAs(learner())->get(route('home'))->assertOk();
});
