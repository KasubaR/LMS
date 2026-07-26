<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_must_change_password_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('password.force.edit'));
    }

    public function test_user_can_complete_forced_password_change_and_reach_dashboard(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->put(route('password.force.update'), [
            'current_password' => 'password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse($user->refresh()->mustChangePassword());

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_force_password_route_itself_does_not_redirect_loop(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get(route('password.force.edit'))->assertOk();
    }

    public function test_wrong_current_password_rejected_on_forced_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->put(route('password.force.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue($user->refresh()->mustChangePassword());
    }
}
