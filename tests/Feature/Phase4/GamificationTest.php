<?php

use App\Models\User;
use App\Modules\Analytics\Models\DailyLearningStatistic;
use App\Modules\Gamification\Models\Badge;
use App\Modules\Gamification\Models\LearningCheckin;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Gamification\Services\BadgeService;
use App\Modules\Gamification\Services\StreakService;
use App\Modules\Gamification\Services\XpService;
use App\Modules\Learning\Models\AppSetting;
use Database\Seeders\BadgeSeeder;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function activeOn(User $user, string $date, int $attempts = 1): void
{
    DailyLearningStatistic::create(['user_id' => $user->id, 'stat_date' => $date, 'attempts_count' => $attempts]);
}

/* ---------------- XP ---------------- */

it('pays an award only once per idempotency key', function () {
    $user = learner();
    $xp = app(XpService::class);

    expect($xp->award($user, 10, 'test', 'key-1'))->toBe(10)
        ->and($xp->award($user, 10, 'test', 'key-1'))->toBe(0)
        ->and($xp->award($user, 0, 'test', 'key-2'))->toBe(0)
        ->and($xp->award($user, -5, 'test', 'key-3'))->toBe(0)
        ->and($xp->total($user))->toBe(10)
        ->and(UserXpTransaction::count())->toBe(1);
});

it('reads XP amounts and multipliers from the admin settings', function () {
    AppSetting::set('xp.per_correct_sentence', 20);
    AppSetting::set('xp.mode_multiplier.easy', 1.0);
    $lesson = publishedLesson(1);
    $user = learner();

    typeAnswer($user, startPractice($user, $lesson, 'easy'), 'sentence number 1');

    expect(UserXpTransaction::where('action', 'dictation_correct')->value('amount'))->toBe(20);
});

/* ---------------- daily check-in and streak ---------------- */

it('checks in once a day and awards XP', function () {
    $user = learner();
    $this->actingAs($user);

    $this->post(route('checkin'))->assertRedirect();
    $this->post(route('checkin'));

    $checkin = LearningCheckin::firstOrFail();

    expect(LearningCheckin::count())->toBe(1)
        ->and($checkin->streak_after)->toBe(1)
        ->and($checkin->xp_awarded)->toBe(5)
        ->and(app(XpService::class)->total($user))->toBe(5);
});

it('adds a streak bonus on following days, up to a limit', function () {
    $user = learner();
    foreach ([1, 2, 3] as $daysAgo) {
        LearningCheckin::create(['user_id' => $user->id, 'checkin_date' => now()->subDays($daysAgo)->toDateString(), 'streak_after' => 4 - $daysAgo]);
    }

    $this->actingAs($user)->post(route('checkin'));

    $checkin = LearningCheckin::latest('id')->first();

    expect($checkin->streak_after)->toBe(4)
        ->and($checkin->xp_awarded)->toBe(5 + 3);
});

it('counts a streak of consecutive active days', function () {
    $user = learner();
    $streak = app(StreakService::class);

    expect($streak->current($user))->toBe(0);

    activeOn($user, now()->toDateString());
    activeOn($user, now()->subDay()->toDateString());
    activeOn($user, now()->subDays(2)->toDateString());
    activeOn($user, now()->subDays(4)->toDateString());   // a gap ends the streak

    expect($streak->current($user))->toBe(3);
});

it('keeps the streak alive until the end of the day', function () {
    $user = learner();
    activeOn($user, now()->subDay()->toDateString());
    activeOn($user, now()->subDays(2)->toDateString());

    expect(app(StreakService::class)->current($user))->toBe(2);
});

it('breaks the streak after a missed day', function () {
    $user = learner();
    activeOn($user, now()->subDays(2)->toDateString());

    expect(app(StreakService::class)->current($user))->toBe(0);
});

it('uses the learner\'s timezone to decide what today is', function () {
    $user = learner();
    $user->ensureProfile()->update(['timezone' => 'Pacific/Kiritimati']);   // UTC+14
    $this->actingAs($user)->post(route('checkin'));

    $expected = now('Pacific/Kiritimati')->toDateString();

    expect(LearningCheckin::firstOrFail()->checkin_date->toDateString())->toBe($expected);
});

/* ---------------- badges ---------------- */

it('awards badges once, with their XP reward', function () {
    $this->seed(BadgeSeeder::class);
    $lesson = publishedLesson(1);
    $user = learner();

    typeAnswer($user, startPractice($user, $lesson), 'sentence number 1');

    $earned = $user->badges()->pluck('code')->all();
    expect($earned)->toContain('first_session');

    $reward = UserXpTransaction::where('action', 'badge')->sum('amount');
    expect($reward)->toBeGreaterThanOrEqual(10);

    // doing it all again does not pay the badge a second time
    typeAnswer($user, startPractice($user, $lesson), 'sentence number 1');
    expect($user->badges()->where('code', 'first_session')->count())->toBe(1)
        ->and(UserXpTransaction::where('action', 'badge')->where('description', 'First Steps')->count())->toBe(1);
});

it('reports earned badges on the final result', function () {
    $this->seed(BadgeSeeder::class);
    $user = learner();
    $session = startPractice($user, publishedLesson(1));

    typeAnswer($user, $session, 'sentence number 1');

    $this->get(route('practice.show', $session))->assertInertia(fn (Assert $page) => $page
        ->where('result.badges.0.name', 'First Steps'));
});

it('unlocks the XP badge when rewards push the total over the line', function () {
    Badge::create(['code' => 'xp_20', 'name' => 'Twenty', 'criteria_type' => 'xp_total', 'criteria_value' => 20, 'xp_reward' => 0]);
    $user = learner();
    app(XpService::class)->award($user, 25, 'test', 'seed');

    $earned = app(BadgeService::class)->check($user);

    expect($earned->pluck('code')->all())->toBe(['xp_20']);
});

it('ignores inactive badges', function () {
    Badge::create(['code' => 'off', 'name' => 'Off', 'criteria_type' => 'xp_total', 'criteria_value' => 1, 'xp_reward' => 0, 'is_active' => false]);
    $user = learner();
    app(XpService::class)->award($user, 5, 'test', 'seed');

    expect(app(BadgeService::class)->check($user))->toHaveCount(0);
});

/* ---------------- leaderboard ---------------- */

function xpFor(User $user, int $amount, ?string $when = null): void
{
    UserXpTransaction::create([
        'user_id' => $user->id, 'amount' => $amount, 'action' => 'test',
        'idempotency_key' => 'k-'.uniqid('', true), 'awarded_at' => $when ?? now(),
    ]);
}

it('ranks learners by XP for each period', function () {
    $first = learner();
    $second = learner();
    $third = learner();
    xpFor($first, 50);
    xpFor($second, 80);
    xpFor($third, 500, now()->subDays(3)->toDateTimeString());
    $this->actingAs($first);

    $this->get(route('leaderboard', ['period' => 'daily']))->assertInertia(fn (Assert $page) => $page
        ->component('leaderboard')
        ->has('entries', 2)
        ->where('entries.0.value', 80)->where('entries.0.rank', 1)
        ->where('entries.1.value', 50)->where('entries.1.is_me', true)
        ->where('me.rank', 2)->where('me.value', 50));

    $this->get(route('leaderboard', ['period' => 'all']))->assertInertia(fn (Assert $page) => $page
        ->has('entries', 3)->where('entries.0.value', 500)->where('me.rank', 3));
});

it('defaults to the weekly ranking and accepts only known periods', function () {
    $this->actingAs(learner());

    $this->get(route('leaderboard'))->assertInertia(fn (Assert $page) => $page->where('period', 'weekly'));
    $this->get(route('leaderboard', ['period' => 'forever']))->assertInertia(fn (Assert $page) => $page->where('period', 'weekly'));
});

it('can rank by study time instead', function () {
    AppSetting::set('leaderboard.metric', 'study_time');
    $a = learner();
    $b = learner();
    DailyLearningStatistic::create(['user_id' => $a->id, 'stat_date' => now()->toDateString(), 'study_seconds' => 600]);
    DailyLearningStatistic::create(['user_id' => $b->id, 'stat_date' => now()->toDateString(), 'study_seconds' => 900]);

    $this->actingAs($a)->get(route('leaderboard', ['period' => 'daily']))->assertInertia(fn (Assert $page) => $page
        ->where('metric', 'study_time')->where('entries.0.value', 900)->where('me.rank', 2));
});

it('protects the privacy of learners', function () {
    $shy = User::factory()->create(['name' => 'Shy Person', 'email' => 'shy@example.com']);
    $shy->assignRole('learner');
    $shy->ensureProfile()->update(['leaderboard_anonymous' => true]);
    $open = User::factory()->create(['name' => 'Open Person', 'email' => 'open@example.com']);
    $open->assignRole('learner');
    $named = User::factory()->create(['name' => 'Full Name Here', 'email' => 'named@example.com']);
    $named->assignRole('learner');
    $named->ensureProfile()->update(['display_name' => 'Nickname']);
    foreach ([$shy, $open, $named] as $index => $user) {
        xpFor($user, 100 - $index);
    }
    $viewer = learner();

    $response = $this->actingAs($viewer)->get(route('leaderboard', ['period' => 'all']));
    $props = null;
    $response->assertInertia(function (Assert $page) use (&$props) {
        $props = $page->toArray()['props'];
    });

    $names = collect($props['entries'])->pluck('name')->all();
    $ranking = json_encode(['entries' => $props['entries'], 'me' => $props['me']]);
    $everything = json_encode($props);

    // The page also carries the signed-in viewer's own email (shared auth data), so the
    // ranking itself is checked for leaks, and no OTHER learner's email may appear anywhere.
    expect($names)->toBe(['Anonymous learner', 'Open', 'Nickname'])
        ->and($ranking)->not->toContain('@')
        ->and($ranking)->not->toContain('Shy')
        ->and($ranking)->not->toContain('Full Name')
        ->and($ranking)->not->toContain('user_id')
        ->and($everything)->not->toContain('shy@example.com')
        ->and($everything)->not->toContain('open@example.com')
        ->and($everything)->not->toContain('named@example.com');
});

it('shows a learner as "You" even when anonymous', function () {
    $user = learner();
    $user->ensureProfile()->update(['leaderboard_anonymous' => true]);
    xpFor($user, 10);

    $this->actingAs($user)->get(route('leaderboard', ['period' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.name', 'You'));
});

/* ---------------- dashboard, analytics, settings ---------------- */

it('builds the learner dashboard', function () {
    $this->seed(BadgeSeeder::class);
    $lesson = publishedLesson(2);
    $user = learner();
    $session = startPractice($user, $lesson);
    typeAnswer($user, $session, 'sentence number 1');
    typeAnswer($user, $session, 'wrong');
    $unfinished = startPractice($user, publishedLesson(3), 'easy');

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.completed_lessons', 1)
        ->where('stats.sessions', 1)
        ->where('stats.streak', 1)
        ->where('stats.average_accuracy', fn ($v) => $v > 50 && $v < 100)
        ->where('stats.xp', fn ($v) => $v > 40)
        ->where('today.sentences', 2)
        ->where('today.checked_in', false)
        ->has('week', 7)
        ->where('week.6.sentences', 2)
        ->where('week.0.sentences', 0)
        ->has('recent', 1)
        ->where('continue.id', $unfinished->id)
        ->has('badges'));
});

it('shows zeros for a brand-new learner', function () {
    $this->actingAs(learner())->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('stats.study_seconds', 0)->where('stats.average_accuracy', null)->where('stats.streak', 0)
        ->where('continue', null)->has('recent', 0)->has('week', 7));
});

it('marks the checked-in state and the goals', function () {
    $user = learner();
    $user->ensureProfile()->update(['daily_goal_minutes' => 30, 'daily_goal_sentences' => 12]);
    $this->actingAs($user)->post(route('checkin'));

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('today.checked_in', true)->where('today.goal_minutes', 30)->where('today.goal_sentences', 12)->where('stats.streak', 1));
});

it('builds the analytics page', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(1));
    typeAnswer($user, $session, 'wrong');
    typeAnswer($user, startPractice($user, publishedLesson(1), 'easy'), 'sentence number 1');

    $this->get(route('analytics'))->assertInertia(fn (Assert $page) => $page
        ->component('analytics')
        ->has('days', 30)
        ->where('days.29.sentences', 2)
        ->has('by_mode', 3)
        ->has('hardest', 1)
        ->where('hardest.0.times_missed', 1));
});

it('saves learning settings', function () {
    $user = learner();
    $this->actingAs($user);

    $this->get(route('learning-settings.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('settings/learning')->where('profile.daily_goal_minutes', 10)->where('profile.default_mode', 'medium'));

    $this->patch(route('learning-settings.update'), [
        'display_name' => 'Tuhin', 'target_cefr_level' => 'B1', 'daily_goal_minutes' => 25, 'daily_goal_sentences' => 40,
        'timezone' => 'Asia/Dhaka', 'leaderboard_anonymous' => '1', 'default_mode' => 'hard', 'default_speed' => '0.75', 'autoplay' => '1',
    ])->assertRedirect(route('learning-settings.edit'));

    $profile = $user->fresh()->profile;   // read again: the language middleware looked at the profile before it existed

    expect($profile->display_name)->toBe('Tuhin')
        ->and($profile->target_cefr_level->value)->toBe('B1')
        ->and($profile->daily_goal_minutes)->toBe(25)
        ->and($profile->timezone)->toBe('Asia/Dhaka')
        ->and($profile->leaderboard_anonymous)->toBeTrue()
        ->and($profile->preferences)->toEqual(['default_mode' => 'hard', 'default_speed' => 0.75, 'autoplay' => true]);   // MySQL keeps JSON keys in its own order

    $this->patch(route('learning-settings.update'), [
        'daily_goal_minutes' => 25, 'daily_goal_sentences' => 40, 'timezone' => 'Asia/Dhaka',
    ]);
    expect($user->fresh()->profile->leaderboard_anonymous)->toBeFalse()
        ->and($user->fresh()->profile->preferences['autoplay'])->toBeFalse();   // an unticked box is sent as nothing
});

it('validates learning settings', function (array $bad, string $field) {
    $valid = ['daily_goal_minutes' => 10, 'daily_goal_sentences' => 10, 'timezone' => 'UTC'];

    $this->actingAs(learner())->patch(route('learning-settings.update'), array_merge($valid, $bad))->assertSessionHasErrors($field);
})->with([
    'zero minutes' => [['daily_goal_minutes' => 0], 'daily_goal_minutes'],
    'too many minutes' => [['daily_goal_minutes' => 999], 'daily_goal_minutes'],
    'too many sentences' => [['daily_goal_sentences' => 501], 'daily_goal_sentences'],
    'unknown timezone' => [['timezone' => 'Mars/Phobos'], 'timezone'],
    'unknown level' => [['target_cefr_level' => 'Z9'], 'target_cefr_level'],
    'unknown mode' => [['default_mode' => 'extreme'], 'default_mode'],
    'unavailable speed' => [['default_speed' => 5], 'default_speed'],
]);

it('keeps every learner page behind the login', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'analytics', 'leaderboard', 'courses.index', 'vocabulary.index', 'mistakes.index', 'notes.index', 'review.index', 'learning-settings.edit']);
