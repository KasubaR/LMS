<?php

namespace App\Http\Controllers;

use App\Models\LencoCollectionRequest;
use App\Services\LencoCollectionFinalizer;
use App\Services\LencoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LencoWebhookController extends Controller
{
    public function __construct(
        private LencoService $lenco,
        private LencoCollectionFinalizer $finalizer,
    ) {}

    /**
     * POST /webhooks/lenco
     *
     * Lenco calls this URL when a transaction's status changes (e.g. a mobile
     * money collection moves from "pay-offline" to "successful"). The exact
     * event name for collections isn't documented, so this matches on the
     * payload's reference rather than the wrapping `event` name.
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->lenco->verifyWebhookSignature($request->getContent(), $request->header('X-Lenco-Signature'))) {
            Log::warning('Lenco webhook signature verification failed');

            return response('Invalid signature', 401);
        }

        $payload = $request->input('data', []);
        $reference = $payload['reference'] ?? null;

        if (! $reference) {
            return response('Missing reference', 400);
        }

        $collectionRequest = LencoCollectionRequest::where('reference', $reference)->first();

        if (! $collectionRequest) {
            Log::warning('Lenco webhook received for unknown reference', ['reference' => $reference]);

            // Return 200 so Lenco doesn't retry indefinitely for an event we'll never match.
            return response('OK', 200);
        }

        $this->finalizer->apply($collectionRequest, $payload, $request->all());

        return response('OK', 200);
    }
}
