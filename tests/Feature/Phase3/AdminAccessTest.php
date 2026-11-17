<?php

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('sends guests to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['admin.dashboard', 'admin.courses.index', 'admin.categories.index', 'admin.audio.index']);

it('keeps learners out of every admin page', function (string $route) {
    $this->actingAs(userWithRole('learner'))->get(route($route))->assertForbidden();
})->with(['admin.dashboard', 'admin.courses.index', 'admin.categories.index', 'admin.audio.index']);

it('lets staff open the admin area', function (string $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard')->has('stats'));
})->with(['content-manager', 'admin', 'super-admin']);

it('shares roles and permissions with the interface', function () {
    $this->actingAs(userWithRole('content-manager'))
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can_access_admin', true)
            ->where('auth.roles', ['content-manager'])
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('content.manage')));
});

it('does not offer admin links to learners', function () {
    $this->actingAs(userWithRole('learner'))
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can_access_admin', false)
            ->where('auth.permissions', []));
});

it('counts content on the dashboard', function () {
    $lesson = lessonTree(true);
    Sentence::factory()->for($lesson)->count(2)->create();

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.courses', 1)
            ->where('stats.published_courses', 1)
            ->where('stats.lessons', 1)
            ->where('stats.sentences', 2)
            ->where('stats.missing_audio', 2));
});

it('does not let a user without the permission create content', function () {
    $user = userWithRole('content-manager');
    Role::findByName('content-manager')->revokePermissionTo('content.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user)->post(route('admin.categories.store'), ['name' => 'Nope'])->assertForbidden();
});

it('never exposes the user model password hash to the page', function () {
    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->missing('auth.user.password'));
});

it('treats the super-admin as allowed everywhere', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('anything.at.all'))->toBeTrue();
});
