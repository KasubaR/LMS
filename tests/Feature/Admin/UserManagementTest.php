<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_manage_users_permission_required_to_view_index(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_user_with_role_and_generated_password(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'New Officer',
            'email' => 'officer@example.com',
            'role' => 'Loan Officer',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('generated_password');

        $newUser = User::where('email', 'officer@example.com')->firstOrFail();
        $this->assertTrue($newUser->hasRole('Loan Officer'));
        $this->assertTrue($newUser->isActive());
    }

    public function test_created_user_has_must_change_password_true(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'New Officer',
            'email' => 'officer@example.com',
            'role' => 'Loan Officer',
        ]);

        $newUser = User::where('email', 'officer@example.com')->firstOrFail();
        $this->assertTrue($newUser->mustChangePassword());
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole('Viewer');

        $this->actingAs($superAdmin)->patch(route('admin.users.toggle-status', $target));

        $this->assertFalse($target->refresh()->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $target = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $target->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_reset_user_password_and_flag_forces_change(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $target = User::factory()->create(['must_change_password' => false]);
        $target->assignRole('Viewer');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.reset-password', $target));

        $response->assertSessionHas('generated_password');
        $this->assertTrue($target->refresh()->mustChangePassword());
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)->patch(route('admin.users.toggle-status', $superAdmin));

        $this->assertTrue($superAdmin->refresh()->is_active);
    }
}
