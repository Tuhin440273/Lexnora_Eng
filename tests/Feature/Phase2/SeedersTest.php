<?php

use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Gamification\Models\Badge;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds roles, settings, badges, an admin and sample content', function () {
    config()->set('learning.admin.password', 'Test-Admin-Pass-1');

    $this->seed();

    $admin = User::where('email', config('learning.admin.email'))->first();

    expect(Role::count())->toBe(4)
        ->and($admin)->not->toBeNull()
        ->and($admin->hasRole('super-admin'))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->profile)->not->toBeNull()
        ->and(Badge::count())->toBe(9)
        ->and(Course::count())->toBe(2)
        ->and(Lesson::count())->toBe(4)
        ->and(Sentence::whereNull('parent_id')->count())->toBe(23)
        ->and(Sentence::whereNotNull('parent_id')->count())->toBe(9)
        ->and(Vocabulary::count())->toBe(13)
        ->and(Vocabulary::doesntHave('sentences')->count())->toBe(0)
        ->and(Lesson::learnerVisible()->count())->toBe(4);
});

it('can be run twice without duplicating anything', function () {
    config()->set('learning.admin.password', 'Test-Admin-Pass-1');

    $this->seed();
    $this->seed();

    expect(Course::count())->toBe(2)
        ->and(Sentence::count())->toBe(32)
        ->and(Vocabulary::count())->toBe(13)
        ->and(Badge::count())->toBe(9)
        ->and(Role::count())->toBe(4)
        ->and(User::where('email', config('learning.admin.email'))->count())->toBe(1);
});

it('does not create an admin when no password is configured', function () {
    config()->set('learning.admin.password', null);

    $this->seed();

    expect(User::where('email', config('learning.admin.email'))->exists())->toBeFalse();
});

it('gives existing users a profile and the learner role', function () {
    $user = User::factory()->create();

    $this->seed();

    expect($user->fresh()->hasRole('learner'))->toBeTrue()
        ->and($user->fresh()->profile)->not->toBeNull();
});

it('keeps admin-edited settings when seeding again', function () {
    $this->seed();
    AppSetting::set('xp.daily_checkin', 99);

    $this->seed();

    expect(AppSetting::get('xp.daily_checkin'))->toBe(99);
});

it('falls back to config defaults before anything is seeded', function () {
    expect(AppSetting::get('review.intervals_days'))->toBe([1, 3, 7, 14, 30, 60])
        ->and(AppSetting::get('does.not.exist', 'fallback'))->toBe('fallback');
});
