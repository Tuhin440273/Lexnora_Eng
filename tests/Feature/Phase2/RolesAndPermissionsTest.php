<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates the four roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'content-manager', 'learner', 'super-admin']);
});

it('is safe to run the seeder twice', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::count())->toBe(4);
});

it('gives the super-admin every permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $granted = Role::findByName('super-admin')->permissions->pluck('name')->sort()->values()->all();
    $all = collect(RolesAndPermissionsSeeder::PERMISSIONS)->sort()->values()->all();

    expect($granted)->toBe($all);
});

it('keeps learners out of the admin area', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('learner');

    expect($user->can('content.manage'))->toBeFalse()
        ->and($user->can('users.manage'))->toBeFalse()
        ->and($user->canAccessAdmin())->toBeFalse()
        ->and($user->isAdmin())->toBeFalse();
});

it('lets content managers edit content but not manage users', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('content-manager');

    expect($user->can('content.manage'))->toBeTrue()
        ->and($user->can('audio.manage'))->toBeTrue()
        ->and($user->can('users.manage'))->toBeFalse()
        ->and($user->canAccessAdmin())->toBeTrue();
});

it('does not let a plain admin manage roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->can('users.manage'))->toBeTrue()
        ->and($user->can('roles.manage'))->toBeFalse()
        ->and($user->isAdmin())->toBeTrue();
});

it('registers new users as learners and creates their profile', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'new-learner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'new-learner@example.com')->firstOrFail();

    expect($user->hasRole('learner'))->toBeTrue()
        ->and($user->profile)->not->toBeNull();
});

it('creates a profile with sensible defaults', function () {
    $profile = User::factory()->create()->ensureProfile()->fresh();

    expect($profile->timezone)->toBe('UTC')
        ->and($profile->native_language)->toBe('bn')
        ->and($profile->daily_goal_minutes)->toBe(10)
        ->and($profile->leaderboard_anonymous)->toBeFalse();
});
