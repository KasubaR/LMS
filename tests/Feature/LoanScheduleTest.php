<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanScheduleGenerator;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array{0: User, 1: Loan}
     */
    private function makeActiveLoan(User $actor, array $terms = []): array
    {
        $customer = Customer::factory()->create();
        $generator = app(LoanScheduleGenerator::class);

        $payload = array_merge([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_BIWEEKLY,
            'start_date' => '2026-06-01',
            'processing_fee' => 0,
        ], $terms);

        $schedule = $generator->generate($payload);

        $loan = Loan::create([
            ...$payload,
            'loan_number' => 'LN-2026-S'.str_pad((string) (Loan::count() + 1), 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'penalty_type' => Loan::PENALTY_FIXED,
            'penalty_value' => 0,
            'due_date' => $schedule['due_date'],
            'next_due_date' => $schedule['installments'][0]['due_date'],
            'status' => Loan::STATUS_ACTIVE,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ]);

        $loan->installments()->createMany($schedule['installments']);

        return [$actor, $loan->fresh('installments')];
    }

    public function test_viewer_can_open_schedule_but_cannot_mark_paid(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager);

        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get(route('loans.schedule', $loan))->assertOk();

        $installment = $loan->installments->first();

        $this->actingAs($viewer)
            ->post(route('loans.installments.mark-paid', [$loan, $installment]))
            ->assertForbidden();
    }

    public function test_accountant_mark_paid_targets_installment(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant);

        $second = $loan->installments->last();

        $this->actingAs($accountant)
            ->post(route('loans.installments.mark-paid', [$loan, $second]))
            ->assertRedirect(route('loans.schedule', $loan));

        $second->refresh();
        $this->assertTrue($second->isPaid());
        $this->assertFalse($loan->fresh('installments')->installments->first()->isPaid());

        $payment = Payment::firstOrFail();
        $this->assertCount(1, $payment->allocations);
        $this->assertSame($second->id, $payment->allocations->first()->loan_installment_id);
    }

    public function test_partial_payment_with_installment_id_shows_partial_status(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $installment = $loan->installments->first();

        $this->actingAs($accountant)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'loan_installment_id' => $installment->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $installment->refresh();
        $this->assertSame(100.0, (float) $installment->amount_paid);
        $this->assertSame('partial', $installment->displayStatus());
        $this->assertFalse($installment->isPaid());
    }

    public function test_cannot_mark_paid_on_already_paid_installment(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $installment = $loan->installments->first();

        $this->actingAs($accountant)
            ->post(route('loans.installments.mark-paid', [$loan, $installment]))
            ->assertRedirect();

        $this->actingAs($accountant)
            ->post(route('loans.installments.mark-paid', [$loan, $installment->fresh()]))
            ->assertSessionHasErrors('installment');
    }

    public function test_overdue_display_status_when_past_due(): void
    {
        Carbon::setTestNow('2026-08-01');

        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
            'start_date' => '2026-06-01',
        ]);

        $this->actingAs($manager)->get(route('loans.schedule', $loan))->assertOk();

        $installment = $loan->fresh('installments')->installments->first();
        $this->assertSame('overdue', $installment->displayStatus());

        Carbon::setTestNow();
    }

    public function test_running_principal_balance_decreases_across_rows(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager);

        $response = $this->actingAs($manager)->get(route('loans.schedule', $loan));
        $response->assertOk();

        $rows = $response->viewData('rows');
        $this->assertCount(2, $rows);
        $this->assertGreaterThan(
            (float) $rows[1]['running_principal_balance'],
            (float) $rows[0]['running_principal_balance']
        );
        $this->assertSame(0.0, (float) $rows[1]['running_principal_balance']);
    }

    public function test_create_payment_page_prefills_installment_target(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant);
        $installment = $loan->installments->first();

        $this->actingAs($accountant)
            ->get(route('payments.create', [
                'loan_id' => $loan->id,
                'installment_id' => $installment->id,
            ]))
            ->assertOk()
            ->assertSee('Target installment')
            ->assertSee('#'.$installment->sequence);
    }
}
