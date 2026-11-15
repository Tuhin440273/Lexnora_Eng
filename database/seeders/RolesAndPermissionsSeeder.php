<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'users.manage',
        'roles.manage',
        'content.manage',
        'content.publish',
        'vocabulary.manage',
        'audio.manage',
        'settings.manage',
        'analytics.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $matrix = [
            'super-admin' => self::PERMISSIONS,
            'admin' => array_values(array_diff(self::PERMISSIONS, ['roles.manage'])),
            'content-manager' => ['content.manage', 'content.publish', 'vocabulary.manage', 'audio.manage'],
            'learner' => [],
        ];

        foreach ($matrix as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
