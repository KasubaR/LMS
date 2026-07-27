<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_viewer_can_view_customers_index_but_cannot_create(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get(route('customers.index'))->assertOk();
        $this->actingAs($viewer)->get(route('customers.create'))->assertForbidden();
    }

    public function test_loan_officer_can_create_customer_with_generated_number(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        $response = $this->actingAs($officer)->post(route('customers.store'), [
            'name' => 'John Banda',
            'nrc' => '123456/78/1',
            'phone' => '0977123456',
            'email' => 'john@example.com',
            'address' => 'Lusaka',
            'occupation' => 'Trader',
            'collateral' => 'Vehicle',
            'notes' => 'Preferred morning calls',
        ]);

        $customer = Customer::where('nrc', '123456/78/1')->firstOrFail();

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertSame(Customer::STATUS_ACTIVE, $customer->status);
        $this->assertMatchesRegularExpression('/^CUS-\d{4}-\d{4}$/', $customer->customer_number);
        $this->assertSame('John Banda', $customer->name);
    }

    public function test_duplicate_nrc_is_rejected(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        Customer::factory()->create(['nrc' => '123456/78/1']);

        $response = $this->actingAs($officer)->post(route('customers.store'), [
            'name' => 'Duplicate',
            'nrc' => '123456/78/1',
            'phone' => '0977000000',
        ]);

        $response->assertSessionHasErrors('nrc');
        $this->assertSame(1, Customer::where('nrc', '123456/78/1')->count());
    }

    public function test_search_finds_customer_by_name_and_nrc(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        Customer::factory()->create(['name' => 'Mary Phiri', 'nrc' => '111111/11/1']);
        Customer::factory()->create(['name' => 'Other Person', 'nrc' => '222222/22/2']);

        $this->actingAs($viewer)
            ->get(route('customers.index', ['q' => 'Mary']))
            ->assertOk()
            ->assertSee('Mary Phiri')
            ->assertDontSee('Other Person');

        $this->actingAs($viewer)
            ->get(route('customers.index', ['q' => '111111/11/1']))
            ->assertOk()
            ->assertSee('Mary Phiri')
            ->assertDontSee('Other Person');
    }

    public function test_archive_hides_from_default_list_and_restore_brings_back(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $customer = Customer::factory()->create(['name' => 'Archive Me']);

        $this->actingAs($manager)
            ->patch(route('customers.archive', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertTrue($customer->refresh()->isArchived());

        $this->actingAs($manager)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('No customers found.')
            ->assertDontSee('>Archive Me</td>', false);

        $this->actingAs($manager)
            ->get(route('customers.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSee('>Archive Me</td>', false);

        $this->actingAs($manager)
            ->patch(route('customers.restore', $customer))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertTrue($customer->refresh()->isActive());
    }

    public function test_viewer_cannot_archive_customers(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create();

        $this->actingAs($viewer)
            ->patch(route('customers.archive', $customer))
            ->assertForbidden();
    }

    public function test_document_upload_download_and_unauthorized_denied(): void
    {
        Storage::fake('local');

        $officer = User::factory()->create();
        $officer->assignRole('Loan Officer');

        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $customer = Customer::factory()->create();

        $file = UploadedFile::fake()->create('nrc-scan.pdf', 100, 'application/pdf');

        $this->actingAs($officer)
            ->post(route('customers.documents.store', $customer), [
                'type' => CustomerDocument::TYPE_NRC,
                'document' => $file,
            ])
            ->assertRedirect(route('customers.edit', $customer));

        $document = $customer->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);

        $this->actingAs($officer)
            ->get(route('customers.documents.download', [$customer, $document]))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('customers.documents.store', $customer), [
                'type' => CustomerDocument::TYPE_OTHER,
                'document' => UploadedFile::fake()->image('extra.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_accountant_can_view_but_cannot_edit(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');

        $customer = Customer::factory()->create();

        $this->actingAs($accountant)->get(route('customers.show', $customer))->assertOk();
        $this->actingAs($accountant)->get(route('customers.edit', $customer))->assertForbidden();
    }
}
