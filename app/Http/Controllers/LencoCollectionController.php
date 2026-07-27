<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lenco\RequestMobileMoneyPaymentRequest;
use App\Models\Loan;
use App\Models\LencoCollectionRequest;
use App\Services\LencoCollectionFinalizer;
use App\Services\LencoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class LencoCollectionController extends Controller
{
    public function __construct(
        private LencoService $lenco,
        private LencoCollectionFinalizer $finalizer,
    ) {}

    public function index(): View
    {
        $collectionRequests = LencoCollectionRequest::query()
            ->with(['loan', 'customer', 'requester'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('lenco.collections.index', [
            'collectionRequests' => $collectionRequests,
        ]);
    }

    public function store(RequestMobileMoneyPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $loan = Loan::findOrFail($data['loan_id']);
        $reference = $this->lenco->generateReference();

        // Create the record first, as "pending", before calling Lenco — so an
        // audit trail survives even if the request to Lenco fails outright.
        $collectionRequest = LencoCollectionRequest::create([
            'reference' => $reference,
            'loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'loan_installment_id' => $data['loan_installment_id'] ?? null,
            'amount' => $data['amount'],
            'phone' => $data['phone'],
            'operator' => $data['operator'],
            'status' => LencoCollectionRequest::STATUS_PENDING,
            'requested_by' => $request->user()->id,
        ]);

        try {
            $result = $this->lenco->collectMobileMoney(
                phone: $data['phone'],
                operator: $data['operator'],
                amount: (float) $data['amount'],
                reference: $reference,
            );

            $this->finalizer->apply($collectionRequest, $result['data'] ?? [], $result);
        } catch (Throwable $exception) {
            Log::error('Lenco mobile money collection request failed', [
                'reference' => $reference,
                'error' => $exception->getMessage(),
            ]);

            $collectionRequest->update([
                'status' => LencoCollectionRequest::STATUS_FAILED,
                'reason_for_failure' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('loans.show', $loan)
                ->with('error', "Mobile money request failed: {$exception->getMessage()}");
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Mobile money payment request sent to {$collectionRequest->phone}.");
    }

    public function refreshStatus(LencoCollectionRequest $lencoCollectionRequest): RedirectResponse
    {
        try {
            $result = $this->lenco->getTransactionStatus($lencoCollectionRequest->reference);
            $this->finalizer->apply($lencoCollectionRequest, $result['data'] ?? [], $result);
        } catch (Throwable $exception) {
            return redirect()->back()->with('error', "Could not refresh status: {$exception->getMessage()}");
        }

        return redirect()->back()->with('status', 'Mobile money request status refreshed.');
    }
}
