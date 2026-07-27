<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPayoutRequest;
use App\Models\Payout;
use App\Services\LencoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayoutController extends Controller
{
    public function __construct(protected LencoService $lenco) {}

    /**
     * List recent payouts (for your own admin dashboard).
     */
    public function index(): JsonResponse
    {
        $payouts = Payout::latest()->paginate(20);

        return response()->json($payouts);
    }

    /**
     * Trigger a mobile money payout to a client.
     * POST /payouts
     */
    public function store(SendPayoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reference = $this->lenco->generateReference();

        // Create the record first, as "pending", before calling Lenco.
        // This guarantees you have a local audit trail even if the
        // request to Lenco times out or the server crashes mid-call.
        $payout = Payout::create([
            'reference' => $reference,
            'recipient_name' => $data['recipient_name'],
            'recipient_phone' => $data['recipient_phone'],
            'operator' => $data['operator'],
            'amount' => $data['amount'],
            'currency' => 'ZMW',
            'status' => 'pending',
            'initiated_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        try {
            $result = $this->lenco->sendToMobileMoney(
                phone: $data['recipient_phone'],
                operator: $data['operator'],
                amount: (float) $data['amount'],
                reference: $reference,
                narration: $data['narration'] ?? "Payment to {$data['recipient_name']}"
            );

            $payout->update([
                'status' => $result['data']['status'] ?? 'pending',
                'lenco_reference' => $result['data']['lencoReference'] ?? null,
                'raw_response' => $result,
            ]);

            return response()->json([
                'message' => 'Payout initiated',
                'payout' => $payout->fresh(),
            ]);
        } catch (Throwable $e) {
            Log::error('Lenco payout failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $payout->update([
                'status' => 'failed',
                'reason_for_failure' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payout failed',
                'error' => $e->getMessage(),
                'payout' => $payout->fresh(),
            ], 422);
        }
    }

    /**
     * Manually re-check a payout's status against Lenco
     * (useful for "pay-offline" / "pending" states while waiting on webhook).
     */
    public function refreshStatus(Payout $payout): JsonResponse
    {
        $result = $this->lenco->getTransferStatus($payout->reference);

        $payout->update([
            'status' => $result['data']['status'] ?? $payout->status,
            'raw_response' => $result,
        ]);

        return response()->json($payout->fresh());
    }
}
