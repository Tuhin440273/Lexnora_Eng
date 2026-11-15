<?php

use App\Models\User;
use App\Modules\Admin\Exceptions\CannotChangeUser;
use App\Modules\Admin\Services\UserAdministrationService;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('shows the user list to admins only', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->actingAs(learner())->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(userWithRole('content-manager'))->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(userWithRole('admin'))->get(route('admin.users.index'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('admin/users/index'));
});

it('lists, searches and filters users without exposing secrets', function () {
    $admin = userWithRole('admin');
    $ann = User::factory()->create(['name' => 'Ann Learner', 'email' => 'ann@school.test']);
    $ann->assignRole('learner');
    $bob = User::factory()->create(['name' => 'Bob Manager']);
    $bob->assignRole('content-manager');
    $off = User::factory()->create(['name' => 'Off Person']);
    $off->assignRole('learner');
    $off->forceFill(['deactivated_at' => now()])->save();
    $this->actingAs($admin);
    $props = null;

    $this->get(route('admin.users.index'))->assertInertia(function (Assert $page) use (&$props) {
        $page->has('users.data', 4);
        $props = $page->toArray()['props'];
    });
    expect(json_encode($props['users']))->not->toContain('password')->not->toContain('remember_token');

    $this->get(route('admin.users.index', ['search' => 'ann@school']))->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Ann Learner'));
    $this->get(route('admin.users.index', ['role' => 'content-manager']))->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Bob Manager'));
    $this->get(route('admin.users.index', ['status' => 'inactive']))->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.is_active', false));
    $this->get(route('admin.users.index', ['role' => 'wizard']))->assertSessionHasErrors('role');
});

it('deactivates and reactivates an account', function () {
    $target = learner();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.users.active', $target));
    expect($target->fresh()->isDeactivated())->toBeTrue();

    $this->patch(route('admin.users.active', $target));
    expect($target->fresh()->isDeactivated())->toBeFalse();
});

it('signs a deactivated user out on the next request', function () {
    $user = learner();
    $user->forceFill(['deactivated_at' => now()])->save();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('keeps an active user signed in', function () {
    $this->actingAs(learner())->get(route('dashboard'))->assertOk();
});

it('protects accounts from lock-out', function () {
    $super = userWithRole('super-admin');
    $otherSuper = userWithRole('super-admin');
    $admin = userWithRole('admin');
    $otherAdmin = userWithRole('admin');

    // nobody changes their own account
    $this->actingAs($admin)->patch(route('admin.users.active', $admin));
    expect($admin->fresh()->isDeactivated())->toBeFalse();

    // an admin cannot touch another admin or a super-admin
    $this->patch(route('admin.users.active', $otherAdmin));
    $this->patch(route('admin.users.active', $super));
    expect($otherAdmin->fresh()->isDeactivated())->toBeFalse()->and($super->fresh()->isDeactivated())->toBeFalse();

    // a super-admin can
    $this->actingAs($super)->patch(route('admin.users.active', $otherAdmin));
    expect($otherAdmin->fresh()->isDeactivated())->toBeTrue();
});

it('never removes the last active super-admin', function () {
    $only = userWithRole('super-admin');
    $retired = userWithRole('super-admin');
    $retired->forceFill(['deactivated_at' => now()])->save();
    $service = app(UserAdministrationService::class);

    expect(fn () => $service->setActive($retired, $only, false))->toThrow(CannotChangeUser::class, 'last active super-admin');
    expect(fn () => $service->setRole($retired, $only, 'learner'))->toThrow(CannotChangeUser::class, 'last active super-admin');
    expect($only->fresh()->hasRole('super-admin'))->toBeTrue();
});

it('lets only users with the roles permission change roles', function () {
    $target = learner();

    $this->actingAs(userWithRole('admin'))->patch(route('admin.users.role', $target), ['role' => 'content-manager'])->assertForbidden();
    expect($target->fresh()->hasRole('learner'))->toBeTrue();

    $this->actingAs(userWithRole('super-admin'))->patch(route('admin.users.role', $target), ['role' => 'content-manager']);
    expect($target->fresh()->hasRole('content-manager'))->toBeTrue()->and($target->fresh()->hasRole('learner'))->toBeFalse();
});

it('validates the role and refuses to change your own', function () {
    $super = userWithRole('super-admin');
    $target = learner();
    $this->actingAs($super);

    $this->patch(route('admin.users.role', $target), ['role' => 'wizard'])->assertSessionHasErrors('role');
    $this->patch(route('admin.users.role', $super), ['role' => 'learner']);

    expect($super->fresh()->hasRole('super-admin'))->toBeTrue();
});

it('lets a super-admin make an admin and remove one', function () {
    $super = userWithRole('super-admin');
    $target = learner();
    $this->actingAs($super);

    $this->patch(route('admin.users.role', $target), ['role' => 'admin']);
    expect($target->fresh()->hasRole('admin'))->toBeTrue();

    $this->patch(route('admin.users.role', $target), ['role' => 'learner']);
    expect($target->fresh()->hasRole('learner'))->toBeTrue();
});

it('shows and edits what each role may do', function () {
    $manager = userWithRole('content-manager');
    $this->actingAs(userWithRole('admin'))->get(route('admin.roles.index'))->assertForbidden();
    $role = Role::findByName('content-manager');

    $this->actingAs(userWithRole('super-admin'))->get(route('admin.roles.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/roles/index')->has('roles', 4)->has('permissions', 8));

    $this->patch(route('admin.roles.update', $role), ['permissions' => ['content.manage']])->assertSessionHasNoErrors();

    expect($manager->fresh()->can('content.manage'))->toBeTrue()
        ->and($manager->fresh()->can('audio.manage'))->toBeFalse()
        ->and($manager->fresh()->can('content.publish'))->toBeFalse();
});

it('rejects unknown permissions and leaves the super-admin role alone', function () {
    $this->actingAs(userWithRole('super-admin'));
    $manager = Role::findByName('content-manager');
    $super = Role::findByName('super-admin');

    $this->patch(route('admin.roles.update', $manager), ['permissions' => ['sudo.everything']])->assertSessionHasErrors('permissions.0');
    $this->patch(route('admin.roles.update', $super), ['permissions' => []]);

    expect($super->fresh()->permissions->count())->toBe(8);
});

it('treats a user without a role as having no access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});
