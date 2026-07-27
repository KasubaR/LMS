<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LencoWebhookController extends Controller
{
    /**
     * POST /webhooks/lenco
     *
     * Lenco calls this URL when a transaction's status changes
     * (e.g. a mobile money payout goes from "pay-offline" to "successful").
     * Register this URL in your Lenco dashboard under webhook settings.
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->verifySignature($request)) {
            Log::warning('Lenco webhook signature verification failed');

            return response('Invalid signature', 401);
        }

        $payload = $request->input('data', []);
        $reference = $payload['reference'] ?? null;

        if (! $reference) {
            return response('Missing reference', 400);
        }

        $payout = Payout::where('reference', $reference)->first();

        if ($payout) {
            $payout->update([
                'status' => $payload['status'] ?? $payout->status,
                'lenco_reference' => $payload['lencoReference'] ?? $payout->lenco_reference,
                'reason_for_failure' => $payload['reasonForFailure'] ?? null,
                'raw_response' => $payload,
            ]);
        } else {
            Log::warning('Lenco webhook received for unknown reference', ['reference' => $reference]);
        }

        // Always return 200 quickly so Lenco doesn't retry unnecessarily.
        return response('OK', 200);
    }

    /**
     * Verify the request actually came from Lenco.
     * Adjust this to match Lenco's actual signing scheme
     * (check their webhook documentation for the header name and algorithm).
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Lenco-Signature');
        $secret = config('services.lenco.webhook_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
