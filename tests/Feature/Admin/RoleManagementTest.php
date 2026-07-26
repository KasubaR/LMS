<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_super_admin_can_view_roles_index(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $this->actingAs($manager)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_role_with_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.roles.store'), [
            'name' => 'Auditor',
            'permissions' => ['view loans', 'view audit logs'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'Auditor')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('view loans'));
        $this->assertTrue($role->hasPermissionTo('view audit logs'));
        $this->assertFalse($role->hasPermissionTo('manage users'));
    }

    public function test_super_admin_can_sync_permissions_on_role_update(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $role = Role::where('name', 'Viewer')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('admin.roles.update', $role), [
            'name' => 'Viewer',
            'permissions' => ['view loans', 'export reports'],
        ]);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('export reports'));
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $role = Role::where('name', 'Viewer')->firstOrFail();
        $other = User::factory()->create();
        $other->assignRole('Viewer');

        $this->actingAs($superAdmin)->delete(route('admin.roles.destroy', $role));

        $this->assertNotNull(Role::find($role->id));
    }
}
