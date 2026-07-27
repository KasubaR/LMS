<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LencoCollectionRequest;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanScheduleGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LencoCollectionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'services.lenco.secret_key' => self::SECRET,
            'services.lenco.base_url' => 'https://api.lenco.co/access/v2',
            'services.lenco.default_bearer' => 'merchant',
        ]);
    }

    /**
     * @return array{0: User, 1: Loan}
     */
    private function makeActiveLoan(User $actor, array $terms = []): array
    {
        $customer = Customer::factory()->create(['phone' => '0971234567']);
        $generator = app(LoanScheduleGenerator::class);

        $payload = array_merge([
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
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

    private function webhookSignature(array $payload): string
    {
        $rawBody = json_encode($payload);

        return hash_hmac('sha512', $rawBody, hash('sha256', self::SECRET));
    }

    public function test_viewer_and_loan_officer_cannot_request_mobile_money_payment(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        [, $loan] = $this->makeActiveLoan($officer);

        $payload = [
            'loan_id' => $loan->id,
            'phone' => '0971234567',
            'operator' => 'mtn',
            'amount' => 100,
        ];

        $this->actingAs($viewer)->post(route('lenco.collections.store'), $payload)->assertForbidden();
        $this->actingAs($officer)->post(route('lenco.collections.store'), $payload)->assertForbidden();

        $this->assertSame(0, LencoCollectionRequest::count());
    }

    public function test_accountant_can_request_mobile_money_payment_and_it_stays_pending(): void
    {
        Http::fake([
            'https://api.lenco.co/access/v2/collections/mobile-money' => Http::response([
                'status' => true,
                'message' => 'Collection initiated',
                'data' => [
                    'status' => 'pay-offline',
                    'lencoReference' => 'LEN-REF-1',
                ],
            ]),
        ]);

        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        [, $loan] = $this->makeActiveLoan($accountant);

        $response = $this->actingAs($accountant)->post(route('lenco.collections.store'), [
            'loan_id' => $loan->id,
            'phone' => '0971234567',
            'operator' => 'mtn',
            'amount' => 100,
        ]);

        $response->assertRedirect(route('loans.show', $loan));

        $collectionRequest = LencoCollectionRequest::firstOrFail();
        $this->assertSame(LencoCollectionRequest::STATUS_PAY_OFFLINE, $collectionRequest->status);
        $this->assertSame('LEN-REF-1', $collectionRequest->lenco_reference);
        $this->assertNull($collectionRequest->payment_id);
        $this->assertSame(0, Payment::count());
    }

    public function test_webhook_confirms_payment_and_allocates_it_exactly_once(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager);

        $collectionRequest = LencoCollectionRequest::create([
            'reference' => 'coll_test_ref_1',
            'loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'amount' => 100,
            'phone' => '0971234567',
            'operator' => 'mtn',
            'status' => LencoCollectionRequest::STATUS_PAY_OFFLINE,
            'requested_by' => $manager->id,
        ]);

        $payload = [
            'event' => 'transaction.successful',
            'data' => [
                'reference' => 'coll_test_ref_1',
                'status' => 'successful',
                'lencoReference' => 'LEN-REF-2',
            ],
        ];

        $headers = ['X-Lenco-Signature' => $this->webhookSignature($payload)];

        $this->postJson(route('webhooks.lenco'), $payload, $headers)->assertOk();
        $this->postJson(route('webhooks.lenco'), $payload, $headers)->assertOk();

        $this->assertSame(1, Payment::count());

        $collectionRequest->refresh();
        $this->assertSame(LencoCollectionRequest::STATUS_SUCCESSFUL, $collectionRequest->status);
        $this->assertNotNull($collectionRequest->payment_id);

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::METHOD_MOBILE_MONEY, $payment->method);
        $this->assertSame(100.0, (float) $payment->amount);
        $this->assertSame('coll_test_ref_1', $payment->reference);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        [, $loan] = $this->makeActiveLoan($manager);

        $collectionRequest = LencoCollectionRequest::create([
            'reference' => 'coll_test_ref_2',
            'loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'amount' => 100,
            'phone' => '0971234567',
            'operator' => 'mtn',
            'status' => LencoCollectionRequest::STATUS_PAY_OFFLINE,
            'requested_by' => $manager->id,
        ]);

        $payload = [
            'event' => 'transaction.successful',
            'data' => [
                'reference' => 'coll_test_ref_2',
                'status' => 'successful',
            ],
        ];

        $this->postJson(route('webhooks.lenco'), $payload, ['X-Lenco-Signature' => 'not-the-right-signature'])
            ->assertUnauthorized();

        $this->assertSame(LencoCollectionRequest::STATUS_PAY_OFFLINE, $collectionRequest->fresh()->status);
        $this->assertSame(0, Payment::count());
    }
}
