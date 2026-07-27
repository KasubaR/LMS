<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardMetrics;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_every_role_can_view_the_dashboard(): void
    {
        foreach (['Super Admin', 'Manager', 'Loan Officer', 'Accountant', 'Viewer'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
    }

    public function test_cards_reflect_real_data(): void
    {
        Carbon::setTestNow('2026-03-15 10:00:00');

        $customer = Customer::factory()->create();

        $activeLoan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'status' => Loan::STATUS_ACTIVE,
            'principal' => 1000,
        ]);

        LoanInstallment::create([
            'loan_id' => $activeLoan->id,
            'sequence' => 1,
            'due_date' => '2026-03-10',
            'principal_amount' => 500,
            'interest_amount' => 200,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 700,
            'amount_paid' => 700,
            'status' => LoanInstallment::STATUS_PAID,
        ]);

        LoanInstallment::create([
            'loan_id' => $activeLoan->id,
            'sequence' => 2,
            'due_date' => '2026-04-10',
            'principal_amount' => 500,
            'interest_amount' => 200,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 700,
            'amount_paid' => 0,
            'status' => LoanInstallment::STATUS_PENDING,
        ]);

        Payment::factory()->create([
            'loan_id' => $activeLoan->id,
            'customer_id' => $customer->id,
            'amount' => 700,
            'paid_at' => '2026-03-15 09:00:00',
            'status' => Payment::STATUS_POSTED,
        ]);

        $overdueLoan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'status' => Loan::STATUS_ACTIVE,
            'principal' => 500,
        ]);

        LoanInstallment::create([
            'loan_id' => $overdueLoan->id,
            'sequence' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 400,
            'interest_amount' => 100,
            'fee_amount' => 0,
            'penalty_amount' => 0,
            'amount_due' => 500,
            'amount_paid' => 0,
            'status' => LoanInstallment::STATUS_OVERDUE,
        ]);

        $cards = app(DashboardMetrics::class)->cards();

        $this->assertSame(1, $cards['total_customers']);
        $this->assertSame(2, $cards['total_loans']);
        $this->assertSame(1, $cards['active_loans']);
        $this->assertSame(1, $cards['overdue_loans']);
        $this->assertSame(700.0, $cards['todays_collections']);
        $this->assertSame(700.0, $cards['monthly_collections']);
        $this->assertSame(1200.0, $cards['outstanding_balance']);
        $this->assertSame(200.0, $cards['interest_earned']);

        Carbon::setTestNow();
    }

    public function test_chart_datasets_have_expected_shapes(): void
    {
        Customer::factory()
            ->has(Loan::factory()->count(2)->state(['status' => Loan::STATUS_ACTIVE]))
            ->create();

        $metrics = app(DashboardMetrics::class);

        $lending = $metrics->monthlyLending();
        $this->assertCount(6, $lending['labels']);
        $this->assertCount(6, $lending['data']);

        $collections = $metrics->monthlyCollections();
        $this->assertCount(6, $collections['labels']);
        $this->assertCount(6, $collections['data']);

        $status = $metrics->loanStatusBreakdown();
        $this->assertCount(5, $status['labels']);
        $this->assertCount(5, $status['data']);
        $this->assertSame(array_sum($status['data']), Loan::count());

        $borrowers = $metrics->topBorrowers();
        $this->assertLessThanOrEqual(5, count($borrowers['labels']));
        $this->assertCount(count($borrowers['labels']), $borrowers['data']);

        $growth = $metrics->loanGrowth();
        $this->assertCount(12, $growth['labels']);
        $this->assertCount(12, $growth['data']);
        $this->assertSame(Loan::count(), end($growth['data']));
    }
}
