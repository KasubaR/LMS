# Lenco Mobile Money Payout — Laravel Implementation

Sends money from your Lenco account balance to a client's mobile money wallet
(MTN/Airtel/Zamtel), triggered from your own website instead of the Lenco dashboard.

## ⚠️ Before you do anything else

Lenco's public API docs clearly document:
- **Collections** (charging a customer's mobile money — money coming IN)
- **Transfers** to a **bank account** (money going OUT)

They do **not** clearly document a mobile-money **payout** endpoint (money going
OUT to a wallet) at the time this was written. The `sendToMobileMoney()` method
in `LencoService.php` is scaffolded with a best-guess endpoint path
(`/transfers/mobile-money`) based on their naming pattern — **it will likely
need adjusting** once you get real documentation.

**Action item:** email Lenco (the API contact from your dashboard, or
support@lenco.co) and ask specifically: *"What is the endpoint and required
parameters for sending a payout from my balance to a customer's mobile money
wallet in Zambia?"* Update `LencoService::sendToMobileMoney()` with the real
path/params once confirmed. Everything else in this scaffold (validation,
logging, webhooks, idempotency) will still apply regardless of the exact
endpoint shape.

## What's included

```
app/Models/Payout.php                          — DB model for audit trail
app/Services/LencoService.php                  — Lenco API wrapper
app/Http/Requests/SendPayoutRequest.php        — input validation
app/Http/Controllers/PayoutController.php      — trigger + list + status-check payouts
app/Http/Controllers/LencoWebhookController.php — receives async status updates
database/migrations/..._create_payouts_table.php
routes/api.php.snippet                          — routes to add
config/services.php.snippet                     — config to add
.env.example.snippet                            — env vars to add
```

## Setup steps

1. **Copy files into your Laravel app** at matching paths (drop the `.snippet`
   suffix logic — those two files' contents get merged into your existing
   `config/services.php` and `routes/api.php`).

2. **Add env vars** from `.env.example.snippet` to your real `.env`, filled
   with your actual sandbox keys first.

3. **Run the migration:**
   ```bash
   php artisan migrate
   ```

4. **Get your Lenco API keys:**
   In your Lenco dashboard → LencoPay/Collections section → generate Public
   Key and Secret Key. Start with sandbox.

5. **Set up authorization.** `SendPayoutRequest::authorize()` checks for a
   `send-payouts` permission — wire this to your actual auth system (Laravel
   policies, Spatie permissions, or a simple role check), since this endpoint
   moves real money.

6. **Register the webhook URL** in your Lenco dashboard settings, pointing to:
   ```
   https://yourdomain.com/api/webhooks/lenco
   ```
   This lets Lenco tell you asynchronously when a "pay-offline" or "pending"
   payout finishes, rather than you polling constantly.

7. **Test in sandbox** with a small amount before switching
   `LENCO_BASE_URL` to production.

## How a payout flows through the code

1. Your frontend/admin panel calls `POST /api/payouts` with recipient details.
2. `PayoutController::store()` creates a `Payout` row as `pending` **before**
   calling Lenco — so you have a record even if the API call fails outright.
3. `LencoService::sendToMobileMoney()` calls Lenco with a unique reference
   (prevents accidental double-sends, since Lenco rejects duplicate references).
4. The response updates the `Payout` row's status immediately.
5. If the status comes back as `pay-offline` or `pending` (the client needs to
   authorize on their phone), Lenco's webhook — handled by
   `LencoWebhookController` — updates the row later when it's actually done.
6. `GET /api/payouts` gives you your own dashboard of what was sent, to whom,
   and its current state — independent of the Lenco dashboard.

## Safety notes baked in

- Unique reference per payout → prevents double-payment on retry/network blips.
- Local DB log created *before* the API call → nothing is silently lost.
- Signature verification stub on the webhook → adjust to Lenco's actual
  signing method once you have their webhook docs; don't skip this in production.
- Authorization check on who can trigger a payout — tighten this to fit your
  actual admin structure.
