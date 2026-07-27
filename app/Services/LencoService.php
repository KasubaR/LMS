<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LencoService
{
    protected string $baseUrl;

    protected string $secretKey;

    protected string $defaultBearer;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.lenco.base_url'), '/');
        $this->secretKey = (string) config('services.lenco.secret_key');
        $this->defaultBearer = (string) config('services.lenco.default_bearer', 'merchant');
    }

    /**
     * Deliberately not checked in the constructor: this service is
     * constructor-injected into controllers, so throwing here would 500 the
     * whole controller (including read-only actions) whenever the key isn't
     * set yet, instead of failing gracefully inside a single request's
     * try/catch.
     */
    protected function client()
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Lenco secret key is not configured.');
        }

        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }

    /**
     * Generate a unique reference for a collection request.
     * Lenco rejects duplicate references, which protects against double-charging on retry.
     */
    public function generateReference(string $prefix = 'coll'): string
    {
        return $prefix.'_'.now()->format('YmdHis').'_'.Str::random(6);
    }

    /**
     * Push a mobile money collection request to a customer's phone.
     * POST /collections/mobile-money — confirmed endpoint (lenco-api.readme.io/v2.0).
     * The customer approves the charge on their own phone; the result comes back
     * either immediately (rare) or later via the Lenco webhook.
     *
     * @param  string  $phone  Customer phone number, e.g. "0971234567"
     * @param  string  $operator  "mtn" | "airtel" | "zamtel"
     * @param  float  $amount  Amount in ZMW
     * @param  string  $reference  Unique reference for this collection request
     */
    public function collectMobileMoney(
        string $phone,
        string $operator,
        float $amount,
        string $reference,
        ?string $bearer = null
    ): array {
        $response = $this->client()->post('/collections/mobile-money', [
            'amount' => $amount,
            'reference' => $reference,
            'phone' => $phone,
            'operator' => $operator,
            'country' => 'zm',
            'bearer' => $bearer ?? $this->defaultBearer,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Re-check a collection's status using our own reference.
     *
     * NOTE: Lenco's docs don't spell out a dedicated "collection by reference"
     * path alongside /collections/mobile-money. This mirrors their documented
     * transaction-lookup pattern (/transaction-by-reference/{reference}) since
     * collections are represented as transactions in their schema. Verify this
     * path against the real sandbox response before relying on it in production.
     */
    public function getTransactionStatus(string $reference): array
    {
        $response = $this->client()->get("/transaction-by-reference/{$reference}");

        return $this->handleResponse($response);
    }

    /**
     * Verify a Lenco webhook's signature.
     * Per Lenco's docs: header `X-Lenco-Signature` is HMAC-SHA512 of the raw
     * request body, keyed with sha256(secret_key) as the "webhook_hash_key".
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (! $signatureHeader) {
            return false;
        }

        $hashKey = hash('sha256', $this->secretKey);
        $expected = hash_hmac('sha512', $rawBody, $hashKey);

        return hash_equals($expected, $signatureHeader);
    }

    protected function handleResponse(Response $response): array
    {
        $body = $response->json();

        if (! $response->successful() || empty($body['status'])) {
            throw new RuntimeException(
                'Lenco API error: '.($body['message'] ?? 'Unknown error'),
                $response->status()
            );
        }

        return $body;
    }
}
