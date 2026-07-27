<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanScheduleGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
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
            'start_date' => '2026-01-01',
            'processing_fee' => 0,
        ], $terms);

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
            'approved_by' => $actor->id,
        ]);

        $loan->installments()->createMany($schedule['installments']);

        return [$actor, $loan->fresh('installments')];
    }

    public function test_viewer_cannot_record_payment_but_loan_officer_cannot_either(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        $this->actingAs($viewer)->get(route('payments.create'))->assertForbidden();
        $this->actingAs($officer)->get(route('payments.create'))->assertForbidden();
    }

    public function test_accountant_can_record_payment(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $response = $this->actingAs($accountant)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();
        $response->assertRedirect(route('payments.show', $payment));
        $this->assertSame(Payment::STATUS_POSTED, $payment->status);
        $this->assertSame(100.0, (float) $loan->fresh('installments')->installments->first()->amount_paid);
    }

    public function test_fifo_splits_across_two_installments(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager); // biweekly = 2 installments

        $this->assertCount(2, $loan->installments);

        $firstDue = (float) $loan->installments[0]->amount_due;
        $payAmount = round($firstDue + 50, 2);

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $payAmount,
            'method' => Payment::METHOD_MOBILE_MONEY,
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $loan->refresh()->load('installments');
        $this->assertTrue($loan->installments[0]->isPaid());
        $this->assertSame(50.0, (float) $loan->installments[1]->amount_paid);

        $payment = Payment::firstOrFail();
        $this->assertCount(2, $payment->allocations);
    }

    public function test_overpayment_is_rejected(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $balance = (float) $loan->balance();

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $balance + 1,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::count());
    }

    public function test_full_payment_completes_loan_and_clears_next_due_date(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $balance = (float) $loan->balance();

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $balance,
            'method' => Payment::METHOD_BANK_TRANSFER,
            'reference' => 'TX-1',
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame(Loan::STATUS_COMPLETED, $loan->status);
        $this->assertNull($loan->next_due_date);
    }

    public function test_partial_payment_updates_next_due_date(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager); // 2 installments

        $first = $loan->installments[0];
        $second = $loan->installments[1];

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => (float) $first->amount_due,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame($second->due_date->toDateString(), $loan->next_due_date?->toDateString());
        $this->assertSame(1, $loan->remainingPayments());
    }

    public function test_reverse_restores_amounts_and_can_reopen_completed_loan(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $balance = (float) $loan->balance();

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $balance,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();
        $this->assertSame(Loan::STATUS_COMPLETED, $loan->fresh()->status);

        $this->actingAs($manager)->patch(route('payments.reverse', $payment), [
            'reversal_reason' => 'Mistake',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertTrue($payment->fresh()->isReversed());
        $this->assertSame(0.0, (float) $loan->fresh('installments')->installments->first()->amount_paid);
        $this->assertContains($loan->fresh()->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]);
    }

    public function test_accountant_cannot_reverse_payment(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $this->actingAs($accountant)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 50,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();

        $this->actingAs($accountant)
            ->patch(route('payments.reverse', $payment))
            ->assertForbidden();
    }

    public function test_edit_reallocates_correctly(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();

        $this->actingAs($manager)->put(route('payments.update', $payment), [
            'amount' => 250,
            'method' => Payment::METHOD_OTHER,
            'paid_at' => now()->toDateTimeString(),
            'notes' => 'Adjusted',
        ])->assertRedirect(route('payments.show', $payment));

        $this->assertSame(250.0, (float) $payment->fresh()->amount);
        $this->assertSame(250.0, (float) $loan->fresh('installments')->installments->first()->amount_paid);
        $this->assertSame(Payment::METHOD_OTHER, $payment->fresh()->method);
    }

    public function test_receipt_pdf_returns_download(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager, [
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'duration_months' => 1,
        ]);

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $payment = Payment::firstOrFail();

        $this->actingAs($manager)
            ->get(route('payments.receipt.pdf', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
