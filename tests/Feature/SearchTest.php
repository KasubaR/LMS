<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_suggest_returns_customer_by_phone_and_loan_by_number(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create([
            'name' => 'Alice Mwansa',
            'phone' => '0977123456',
            'nrc' => '123456/78/1',
        ]);

        $loan = Loan::factory()->create([
            'customer_id' => $customer->id,
            'loan_number' => 'LN-2026-9999',
        ]);

        $phoneResponse = $this->actingAs($viewer)
            ->getJson(route('search.suggest', ['q' => '0977123456']));

        $phoneResponse->assertOk();
        $phoneResponse->assertJsonFragment([
            'label' => 'Alice Mwansa',
            'type' => 'customer',
            'url' => route('customers.show', $customer),
        ]);

        $loanResponse = $this->actingAs($viewer)
            ->getJson(route('search.suggest', ['q' => 'LN-2026-9999']));

        $loanResponse->assertOk();
        $loanResponse->assertJsonFragment([
            'label' => 'LN-2026-9999',
            'type' => 'loan',
            'url' => route('loans.show', $loan),
        ]);
    }

    public function test_search_results_page_shows_hits(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create([
            'name' => 'Chanda Banda',
            'nrc' => '654321/12/1',
        ]);

        Loan::factory()->create([
            'customer_id' => $customer->id,
            'loan_number' => 'LN-2026-4242',
        ]);

        $response = $this->actingAs($viewer)->get(route('search', ['q' => 'Chanda']));

        $response->assertOk();
        $response->assertSee('Chanda Banda');
        $response->assertSee('LN-2026-4242');
    }

    public function test_suggest_respects_customer_permission(): void
    {
        $user = User::factory()->create();
        // Viewer has both by default — strip customers permission via a custom role isn't easy.
        // Use a user with only view loans: Loan Officer has both. Create role-less with only loans.
        $user->givePermissionTo('view loans');

        $customer = Customer::factory()->create([
            'name' => 'Hidden Person',
            'phone' => '0966000111',
        ]);

        Loan::factory()->create([
            'customer_id' => $customer->id,
            'loan_number' => 'LN-2026-1111',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => '0966000111']));

        $response->assertOk();
        $response->assertJsonPath('customers', []);
        $response->assertJsonFragment(['label' => 'LN-2026-1111']);
    }

    public function test_loans_index_filters_by_officer_date_amount_and_status(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $officerA = User::factory()->create();
        $officerA->assignRole('Loan Officer');

        $officerB = User::factory()->create();
        $officerB->assignRole('Loan Officer');

        $match = Loan::factory()->create([
            'loan_officer_id' => $officerA->id,
            'principal' => 2500,
            'due_date' => '2026-03-15',
            'status' => Loan::STATUS_ACTIVE,
            'loan_number' => 'LN-2026-8001',
        ]);

        Loan::factory()->create([
            'loan_officer_id' => $officerB->id,
            'principal' => 9000,
            'due_date' => '2026-08-01',
            'status' => Loan::STATUS_COMPLETED,
            'loan_number' => 'LN-2026-8002',
        ]);

        $response = $this->actingAs($manager)->get(route('loans.index', [
            'status' => Loan::STATUS_ACTIVE,
            'loan_officer_id' => $officerA->id,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'amount_min' => 2000,
            'amount_max' => 3000,
        ]));

        $response->assertOk();
        $response->assertSee('LN-2026-8001');
        $response->assertDontSee('LN-2026-8002');
        $this->assertTrue($match->is($match));
    }

    public function test_loan_search_finds_by_customer_phone(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create(['phone' => '0955111222']);
        Loan::factory()->create([
            'customer_id' => $customer->id,
            'loan_number' => 'LN-2026-5555',
        ]);

        $response = $this->actingAs($viewer)->get(route('loans.index', ['q' => '0955111222']));

        $response->assertOk();
        $response->assertSee('LN-2026-5555');
    }
}
