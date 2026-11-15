<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\RolePermissionsRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const LABELS = [
        'users.manage' => 'See users, deactivate and reactivate accounts',
        'roles.manage' => 'Change roles of users and what each role may do',
        'content.manage' => 'Create and edit categories, courses, chapters, lessons and sentences',
        'content.publish' => 'Publish and unpublish content',
        'vocabulary.manage' => 'Manage the vocabulary list',
        'audio.manage' => 'Upload and delete audio',
        'settings.manage' => 'Change scoring, hints, review, XP and leaderboard settings',
        'analytics.view' => 'See learning analytics for all learners',
    ];

    public function index(): Response
    {
        $counts = DB::table('model_has_roles')->selectRaw('role_id, count(*) as total')->groupBy('role_id')->pluck('total', 'role_id');

        return Inertia::render('admin/roles/index', [
            'roles' => Role::with('permissions:id,name')->orderBy('id')->get()->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'locked' => $role->name === 'super-admin',
                'permissions' => $role->permissions->pluck('name')->values(),
                'users' => (int) ($counts[$role->id] ?? 0),
            ]),
            'permissions' => collect(RolesAndPermissionsSeeder::PERMISSIONS)->map(fn (string $name) => [
                'name' => $name,
                'label' => self::LABELS[$name] ?? $name,
            ])->values(),
        ]);
    }

    public function update(RolePermissionsRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super-admin') {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The super-admin role always has every permission.')]);

            return back();
        }

        $role->syncPermissions($request->validated('permissions'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permissions saved.')]);

        return back();
    }
}
