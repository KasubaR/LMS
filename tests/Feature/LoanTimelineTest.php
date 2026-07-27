<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanPenaltyCalculator;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_from_loan_timeline(): void
    {
        $loan = $this->makeLoan(User::factory()->create());

        $this->get(route('loans.timeline', $loan))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_view_loans_cannot_open_timeline(): void
    {
        $user = User::factory()->create();
        $loan = $this->makeLoan(User::factory()->create());

        $this->actingAs($user)
            ->get(route('loans.timeline', $loan))
            ->assertForbidden();
    }

    public function test_timeline_shows_created_approved_and_money_disbursed_after_create(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create();

        $this->actingAs($manager)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'loan_officer_id' => $manager->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-15',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 0,
        ])->assertRedirect();

        $loan = Loan::firstOrFail();

        $this->actingAs($manager)
            ->get(route('loans.timeline', $loan))
            ->assertOk()
            ->assertSee('Loan Created', false)
            ->assertSee('Approved', false)
            ->assertSee('Money Disbursed', false);
    }

    public function test_timeline_shows_payment_received_after_payment(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $loan = $this->makeLoanViaHttp($manager);

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $this->actingAs($manager)
            ->get(route('loans.timeline', $loan))
            ->assertOk()
            ->assertSee('Payment K100.00 Received', false);
    }

    public function test_timeline_shows_loan_closed_after_complete(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $loan = $this->makeLoanViaHttp($manager);

        $this->actingAs($manager)
            ->patch(route('loans.complete', $loan))
            ->assertRedirect();

        $this->actingAs($manager)
            ->get(route('loans.timeline', $loan))
            ->assertOk()
            ->assertSee('Loan Closed', false);
    }

    public function test_refresh_loan_logs_payment_missed_and_penalty_applied(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create();

        $this->actingAs($manager)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'loan_officer_id' => $manager->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 25,
        ])->assertRedirect();

        $loan = Loan::with('installments')->firstOrFail();

        app(LoanPenaltyCalculator::class)->refreshLoan($loan, Carbon::parse('2026-03-01'));

        $this->actingAs($manager)
            ->get(route('loans.timeline', $loan))
            ->assertOk()
            ->assertSee('Payment Missed', false)
            ->assertSee('Penalty Applied', false);
    }

    public function test_viewer_can_open_timeline_and_show_links_to_history(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $loan = $this->makeLoanViaHttp($manager);

        $this->actingAs($viewer)
            ->get(route('loans.timeline', $loan))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('loans.show', $loan))
            ->assertOk()
            ->assertSee(route('loans.timeline', $loan), false);
    }

    private function makeLoan(User $actor): Loan
    {
        $customer = Customer::factory()->create();

        return Loan::factory()->create([
            'customer_id' => $customer->id,
            'loan_officer_id' => $actor->id,
            'status' => Loan::STATUS_ACTIVE,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ]);
    }

    private function makeLoanViaHttp(User $actor): Loan
    {
        $customer = Customer::factory()->create();

        $this->actingAs($actor)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'loan_officer_id' => $actor->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-15',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 0,
        ])->assertRedirect();

        return Loan::firstOrFail();
    }
}
