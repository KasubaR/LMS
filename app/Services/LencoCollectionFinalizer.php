<?php

namespace App\Services;

use App\Models\LencoCollectionRequest;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Applies a status update from Lenco (via webhook or a manual refresh) to a
 * LencoCollectionRequest, and — exactly once — turns a successful collection
 * into a real Payment. Shared by the webhook and the manual refresh action so
 * both paths use the same idempotency guard.
 */
class LencoCollectionFinalizer
{
    public function __construct(private PaymentAllocator $allocator) {}

    /**
     * @param  array{status?: string, lencoReference?: string, reasonForFailure?: string}  $data
     */
    public function apply(LencoCollectionRequest $collectionRequest, array $data, array $rawResponse): LencoCollectionRequest
    {
        return DB::transaction(function () use ($collectionRequest, $data, $rawResponse) {
            $collectionRequest = LencoCollectionRequest::query()->lockForUpdate()->findOrFail($collectionRequest->id);

            $status = $data['status'] ?? $collectionRequest->status;

            $collectionRequest->update([
                'status' => $status,
                'lenco_reference' => $data['lencoReference'] ?? $collectionRequest->lenco_reference,
                'reason_for_failure' => $data['reasonForFailure'] ?? $collectionRequest->reason_for_failure,
                'raw_response' => $rawResponse,
            ]);

            if ($status === LencoCollectionRequest::STATUS_SUCCESSFUL && $collectionRequest->payment_id === null) {
                $this->allocatePayment($collectionRequest);
            }

            return $collectionRequest->fresh();
        });
    }

    private function allocatePayment(LencoCollectionRequest $collectionRequest): void
    {
        try {
            $payment = $this->allocator->record([
                'loan_id' => $collectionRequest->loan_id,
                'loan_installment_id' => $collectionRequest->loan_installment_id,
                'amount' => (float) $collectionRequest->amount,
                'method' => Payment::METHOD_MOBILE_MONEY,
                'reference' => $collectionRequest->reference,
                'paid_at' => now(),
                'notes' => 'Collected via Lenco mobile money ('.$collectionRequest->operatorLabel().')',
                'recorded_by' => $collectionRequest->requested_by,
            ]);

            $collectionRequest->update(['payment_id' => $payment->id]);
        } catch (InvalidArgumentException $exception) {
            // Lenco confirmed the charge but the loan can no longer accept it
            // (e.g. already settled by another payment). Money was collected,
            // so this needs manual reconciliation rather than silent failure.
            Log::error('Lenco collection succeeded but could not be allocated to the loan', [
                'reference' => $collectionRequest->reference,
                'loan_id' => $collectionRequest->loan_id,
                'error' => $exception->getMessage(),
            ]);

            $collectionRequest->update([
                'reason_for_failure' => 'Payment received but could not be allocated: '.$exception->getMessage(),
            ]);
        }
    }
}
