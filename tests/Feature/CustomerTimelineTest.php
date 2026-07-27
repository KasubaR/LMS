<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_from_timeline(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.timeline', $customer))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_view_customers_cannot_open_timeline(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->get(route('customers.timeline', $customer))
            ->assertForbidden();
    }

    public function test_timeline_shows_customer_created_after_create(): void
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
            'notes' => null,
        ])->assertRedirect();

        $customer = Customer::where('nrc', '123456/78/1')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('customers.timeline', $customer))
            ->assertOk()
            ->assertSee('Customer Created', false)
            ->assertDontSee('Login', false);
    }

    public function test_timeline_shows_loan_created_and_approved_with_loan_number(): void
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
            'notes' => null,
        ])->assertRedirect();

        $loan = Loan::firstOrFail();

        $this->actingAs($manager)
            ->get(route('customers.timeline', $customer))
            ->assertOk()
            ->assertSee("Loan #{$loan->loan_number} Created", false)
            ->assertSee("Loan #{$loan->loan_number} Approved", false);
    }

    public function test_timeline_shows_formatted_payment_amount(): void
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
        ]);

        $loan = Loan::firstOrFail();

        $this->actingAs($manager)->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $this->actingAs($manager)
            ->get(route('customers.timeline', $customer))
            ->assertOk()
            ->assertSee('Payment K100.00', false);
    }

    public function test_phone_only_update_shows_phone_updated(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create([
            'name' => 'Mary Phiri',
            'nrc' => '111111/11/1',
            'phone' => '0977000001',
        ]);

        $this->actingAs($manager)->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'nrc' => $customer->nrc,
            'phone' => '0977999999',
            'email' => $customer->email,
            'address' => $customer->address,
            'occupation' => $customer->occupation,
            'collateral' => $customer->collateral,
            'notes' => $customer->notes,
        ])->assertRedirect();

        $this->actingAs($manager)
            ->get(route('customers.timeline', $customer))
            ->assertOk()
            ->assertSee('Phone Updated', false);
    }

    public function test_archive_shows_customer_archived_and_viewer_can_open_timeline(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create(['name' => 'Archive Me']);

        $this->actingAs($manager)
            ->patch(route('customers.archive', $customer))
            ->assertRedirect();

        $this->actingAs($viewer)
            ->get(route('customers.timeline', $customer))
            ->assertOk()
            ->assertSee('Customer Archived', false)
            ->assertDontSee('>Login</', false);
    }

    public function test_customer_show_links_to_history(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create();

        $this->actingAs($viewer)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee(route('customers.timeline', $customer), false);
    }
}
