<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoginHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanScheduleGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_success_creates_login_history_and_logout_sets_logout_at(): void
    {
        $user = User::factory()->create([
            'email' => 'audit@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'audit@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $history = LoginHistory::query()
            ->where('email', 'audit@example.com')
            ->where('failed', false)
            ->first();

        $this->assertNotNull($history);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertNotNull(
            $history->fresh()->logout_at,
            'Logout should set logout_at on the open login_history row.'
        );
    }

    public function test_failed_login_creates_failed_login_history(): void
    {
        User::factory()->create([
            'email' => 'fail@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'fail@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_histories', [
            'email' => 'fail@example.com',
            'failed' => true,
        ]);
    }

    public function test_customer_create_creates_labeled_activity_with_causer(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $this->actingAs($manager)->post(route('customers.store'), [
            'name' => 'John Banda',
            'nrc' => '123456/78/1',
            'phone' => '0977123456',
            'email' => 'john@example.com',
            'address' => 'Lusaka',
            'occupation' => 'Trader',
            'collateral' => 'Vehicle',
            'notes' => 'Preferred morning calls',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Customer Created',
            'causer_id' => $manager->id,
        ]);
    }

    public function test_audit_log_index_is_forbidden_without_view_audit_logs_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_customer_update_renders_old_and_new_values_in_audit_log_table(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create([
            'name' => 'Mary Phiri',
            'nrc' => '111111/11/1',
            'phone' => '0977000001',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Trigger a LogsActivity "updated" event with attribute_changes.
        $this->actingAs($manager)->put(route('customers.update', $customer), [
            'name' => 'Mary Phiri Updated',
            'phone' => '0977000001',
            'nrc' => '111111/11/1',
            'email' => $customer->email,
            'address' => $customer->address,
            'occupation' => $customer->occupation,
            'collateral' => $customer->collateral,
            'notes' => $customer->notes,
            'status' => $customer->status,
        ])->assertRedirect();

        $response = $this->actingAs($manager)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Mary Phiri', false);
        $response->assertSee('Mary Phiri Updated', false);
    }

    public function test_payment_reverse_creates_payment_reversed_labeled_activity(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create();
        $generator = app(LoanScheduleGenerator::class);

        $payload = [
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
        ];

        $schedule = $generator->generate($payload);

        $loan = Loan::create([
            ...$payload,
            'loan_number' => 'LN-2026-'.str_pad((string) (Loan::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 0,
            'due_date' => $schedule['due_date'],
            'status' => Loan::STATUS_ACTIVE,
            'approved_at' => now(),
            'approved_by' => $manager->id,
            'loan_officer_id' => $manager->id,
        ]);

        $loan->installments()->createMany($schedule['installments']);

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();

        $this->actingAs($manager)->patch(route('payments.reverse', $payment), [
            'reversal_reason' => 'Mistake',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Payment Reversed',
            'causer_id' => $manager->id,
        ]);
    }

    public function test_role_update_and_password_change_create_labeled_activities(): void
    {
        $super = User::factory()->create();
        $super->assignRole('Super Admin');

        $role = Role::query()->where('name', 'Viewer')->first();
        $this->assertNotNull($role);

        $this->actingAs($super)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->all(),
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Role Updated',
            'causer_id' => $super->id,
        ]);

        $this->actingAs($super)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Password Changed',
            'causer_id' => $super->id,
        ]);
    }
}
