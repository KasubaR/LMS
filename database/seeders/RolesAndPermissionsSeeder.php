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
            'record payments', 'reverse payments', 'view reports', 'export reports', 'manage users', 'view audit logs',
            'view customers', 'create customers', 'edit customers', 'archive customers',
        ],
        'Manager' => [
            'view loans', 'create loans', 'edit loans', 'delete loans',
            'record payments', 'reverse payments', 'view reports', 'export reports', 'view audit logs',
            'view customers', 'create customers', 'edit customers', 'archive customers',
        ],
        'Loan Officer' => [
            'view loans', 'create loans', 'edit loans', 'view reports',
            'view customers', 'create customers', 'edit customers',
        ],
        'Accountant' => [
            'view loans', 'record payments', 'view reports', 'export reports',
            'view customers',
        ],
        'Viewer' => [
            'view loans', 'view reports',
            'view customers',
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
