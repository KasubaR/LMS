<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Default permission-to-role matrix. Adjustable later via the Role Management UI.
     */
    private const MATRIX = [
        'Super Admin' => [
            'view loans', 'create loans', 'edit loans', 'delete loans',
            'record payments', 'export reports', 'manage users', 'view audit logs',
        ],
        'Manager' => [
            'view loans', 'create loans', 'edit loans', 'delete loans',
            'record payments', 'export reports', 'view audit logs',
        ],
        'Loan Officer' => [
            'view loans', 'create loans', 'edit loans',
        ],
        'Accountant' => [
            'view loans', 'record payments', 'export reports',
        ],
        'Viewer' => [
            'view loans',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::MATRIX)->flatten()->unique();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (self::MATRIX as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
