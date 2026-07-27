<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanScheduleGenerator;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_viewer_can_view_loans_but_cannot_create(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get(route('loans.index'))->assertOk();
        $this->actingAs($viewer)->get(route('loans.create'))->assertForbidden();
    }

    public function test_loan_officer_can_create_flat_monthly_one_month_loan(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        $customer = Customer::factory()->create();
        $start = Carbon::parse('2026-01-15');

        $response = $this->actingAs($officer)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'loan_officer_id' => $officer->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => $start->toDateString(),
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_DAILY_PERCENT,
            'penalty_value' => 0,
            'notes' => 'Test loan',
        ]);

        $loan = Loan::firstOrFail();
        $response->assertRedirect(route('loans.show', $loan));

        $this->assertSame(Loan::STATUS_ACTIVE, $loan->status);
        $this->assertMatchesRegularExpression('/^LN-\d{4}-\d{4}$/', $loan->loan_number);
        $this->assertCount(1, $loan->installments);
        $this->assertSame('400.00', number_format((float) $loan->installments->first()->interest_amount, 2, '.', ''));
        $this->assertSame('2026-02-15', $loan->due_date->toDateString());
        $this->assertSame('2026-02-15', $loan->installments->first()->due_date->toDateString());
    }

    public function test_weekly_and_biweekly_installment_counts(): void
    {
        $generator = app(LoanScheduleGenerator::class);

        $weekly = $generator->generate([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_WEEKLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
        ]);

        $biweekly = $generator->generate([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_BIWEEKLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
        ]);

        $this->assertSame(4, $weekly['installment_count']);
        $this->assertSame(2, $biweekly['installment_count']);
    }

    public function test_reducing_schedule_principal_sums_to_original(): void
    {
        $generator = app(LoanScheduleGenerator::class);

        $schedule = $generator->generate([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_REDUCING,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_WEEKLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 50,
        ]);

        $principalSum = round(array_sum(array_column($schedule['installments'], 'principal_amount')), 2);
        $this->assertSame(1000.0, $principalSum);
        $this->assertSame(50.0, (float) $schedule['installments'][0]['fee_amount']);
        $this->assertSame(0.0, (float) $schedule['installments'][1]['fee_amount']);
    }

    public function test_start_date_month_end_clamping(): void
    {
        $generator = app(LoanScheduleGenerator::class);

        $schedule = $generator->generate([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-31',
            'processing_fee' => 0,
        ]);

        $this->assertSame('2026-02-28', $schedule['due_date']);
    }

    public function test_new_loan_is_active_immediately_and_can_be_deleted_before_first_payment(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create();

        $this->actingAs($manager)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 10,
        ]);

        $loan = Loan::firstOrFail();
        $this->assertSame(Loan::STATUS_ACTIVE, $loan->status);
        $this->assertNotNull($loan->next_due_date);

        $this->actingAs($manager)->delete(route('loans.destroy', $loan))->assertRedirect(route('loans.index'));
        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
    }

    public function test_first_payment_locks_loan_from_further_edits(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create();

        $this->actingAs($manager)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 10,
        ]);

        $loan = Loan::firstOrFail();
        $this->actingAs($manager)->get(route('loans.edit', $loan))->assertOk();

        $installment = $loan->installments->first();
        $this->actingAs($manager)->post(route('loans.installments.mark-paid', [$loan, $installment]));

        $this->actingAs($manager)->get(route('loans.edit', $loan))->assertForbidden();
        $this->actingAs($manager)->delete(route('loans.destroy', $loan))->assertForbidden();
    }

    public function test_overdue_detection_when_installment_past_due(): void
    {
        Carbon::setTestNow('2026-03-15');

        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        $customer = Customer::factory()->create();

        $this->actingAs($manager)->post(route('loans.store'), [
            'customer_id' => $customer->id,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 25,
        ]);

        $loan = Loan::firstOrFail();

        $this->actingAs($manager)->get(route('loans.show', $loan))->assertOk();

        $this->assertSame(Loan::STATUS_OVERDUE, $loan->refresh()->status);
        $this->assertSame('25.00', number_format((float) $loan->installments->first()->penalty_amount, 2, '.', ''));

        Carbon::setTestNow();
    }

    public function test_viewer_cannot_delete_loans(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $loan = Loan::factory()->create();

        $this->actingAs($viewer)->delete(route('loans.destroy', $loan))->assertForbidden();
    }

    public function test_loan_numbers_are_unique_across_creates(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'principal' => 500,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_DAILY_PERCENT,
            'penalty_value' => 0,
        ];

        $this->actingAs($officer)->post(route('loans.store'), $payload);
        $this->actingAs($officer)->post(route('loans.store'), $payload);

        $numbers = Loan::query()->pluck('loan_number');
        $this->assertCount(2, $numbers);
        $this->assertCount(2, $numbers->unique());
    }

    public function test_preview_schedule_endpoint_returns_json_schedule(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        $response = $this->actingAs($officer)->postJson(route('loans.preview-schedule'), [
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-15',
            'processing_fee' => 0,
        ]);

        $response->assertOk();
        $response->assertJsonPath('installment_count', 1);
        $response->assertJsonPath('due_date', '2026-02-15');
        $response->assertJsonPath('total_interest', '400.00');
    }

    public function test_viewer_cannot_preview_schedule(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->postJson(route('loans.preview-schedule'), [
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => '2026-01-15',
            'processing_fee' => 0,
        ])->assertForbidden();
    }
}
