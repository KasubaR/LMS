<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerDocumentRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Services\CustomerNumberGenerator;
use App\Services\CustomerTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $customers = Customer::query()
            ->search($request->string('q')->toString())
            ->when(
                $status === Customer::STATUS_ARCHIVED,
                fn ($query) => $query->archived(),
                fn ($query) => $status === 'all' ? $query : $query->active()
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
        ]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request, CustomerNumberGenerator $numbers): RedirectResponse
    {
        $customer = Customer::create([
            ...$request->validated(),
            'customer_number' => $numbers->generate(),
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" created.");
    }

    public function show(Customer $customer): View
    {
        $customer->load(['documents' => fn ($query) => $query->latest()]);

        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    public function timeline(Customer $customer, CustomerTimeline $timeline): View
    {
        return view('customers.timeline', [
            'customer' => $customer,
            'entries' => $timeline->forCustomer($customer),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['documents' => fn ($query) => $query->latest()]);

        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" updated.");
    }

    public function archive(Customer $customer): RedirectResponse
    {
        if ($customer->isArchived()) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', 'Customer is already archived.');
        }

        $customer->update(['status' => Customer::STATUS_ARCHIVED]);

        return redirect()
            ->route('customers.index')
            ->with('status', "Customer \"{$customer->name}\" archived.");
    }

    public function restore(Customer $customer): RedirectResponse
    {
        if ($customer->isActive()) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', 'Customer is already active.');
        }

        $customer->update(['status' => Customer::STATUS_ACTIVE]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" restored.");
    }

    public function storeDocument(StoreCustomerDocumentRequest $request, Customer $customer): RedirectResponse
    {
        $file = $request->file('document');
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'-'.($safeName !== '' ? $safeName : 'document').'.'.$extension;
        $path = $file->storeAs("customers/{$customer->id}", $filename, 'local');

        $customer->documents()->create([
            'type' => $request->validated('type'),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('customers.edit', $customer)
            ->with('status', 'Document uploaded.');
    }

    public function downloadDocument(Customer $customer, CustomerDocument $document): StreamedResponse
    {
        abort_unless($document->customer_id === $customer->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroyDocument(Customer $customer, CustomerDocument $document): RedirectResponse
    {
        abort_unless($document->customer_id === $customer->id, 404);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()
            ->route('customers.edit', $customer)
            ->with('status', 'Document deleted.');
    }
}
