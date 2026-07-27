<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LencoService
{
    protected string $baseUrl;

    protected string $secretKey;

    protected string $debitAccountId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.lenco.base_url'), '/');
        $this->secretKey = config('services.lenco.secret_key');
        $this->debitAccountId = config('services.lenco.debit_account_id');

        if (empty($this->secretKey)) {
            throw new RuntimeException('Lenco secret key is not configured.');
        }
    }

    protected function client()
    {
        return Http::withToken($this->secretKey)
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }

    /**
     * Generate a unique reference for a transaction.
     * Lenco rejects duplicate references, which protects you from double-sends.
     */
    public function generateReference(string $prefix = 'payout'): string
    {
        return $prefix.'_'.now()->format('YmdHis').'_'.Str::random(6);
    }

    /**
     * Send money from your Lenco balance to a customer's mobile money wallet.
     *
     * IMPORTANT: The exact endpoint path/params below (/transfers/mobile-money)
     * are a best-guess based on Lenco's naming conventions for their other
     * "collections" endpoints (e.g. /collections/mobile-money). Confirm the
     * real payout endpoint and required fields with Lenco support before
     * going live — their public docs at the time of writing only clearly
     * document mobile money COLLECTIONS (money coming in), not payouts out.
     *
     * @param  string  $phone  Recipient phone number, e.g. "0971234567"
     * @param  string  $operator  "mtn" | "airtel" | "zamtel"
     * @param  float  $amount  Amount in ZMW
     * @param  string  $reference  Unique reference for this transfer
     * @param  string  $narration  Description shown to recipient / in your records
     */
    public function sendToMobileMoney(
        string $phone,
        string $operator,
        float $amount,
        string $reference,
        string $narration = ''
    ): array {
        $response = $this->client()->post('/transfers/mobile-money', [
            'debitAccountId' => $this->debitAccountId,
            'phone' => $phone,
            'operator' => $operator,
            'country' => 'zm',
            'amount' => $amount,
            'reference' => $reference,
            'narration' => $narration,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Fallback: send to a bank account instead of mobile money.
     * This mirrors Lenco's documented /transfer endpoint (bank transfers),
     * which IS confirmed to exist in their public API.
     */
    public function sendToBankAccount(
        string $accountNumber,
        string $bankCode,
        float $amount,
        string $reference,
        string $narration = ''
    ): array {
        $response = $this->client()->post('/transfer', [
            'debitAccountId' => $this->debitAccountId,
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
            'amount' => $amount,
            'reference' => $reference,
            'narration' => $narration,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Check the status of a transfer using your own reference.
     */
    public function getTransferStatus(string $reference): array
    {
        $response = $this->client()->get("/transfer/by-reference/{$reference}");

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
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
