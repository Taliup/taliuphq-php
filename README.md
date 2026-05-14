# Taliup PHP SDK

Official PHP library for the TaliupHQ Hosted Payments API.

## Install

```bash
composer require taliup/taliup-php
```

Requires PHP 8.2+.

## Configuration

Get your credentials from **Taliup → Settings → Payments → Credentials**.

| Option | Required | Default | Description |
|---|---|---|---|
| `merchant_site_id` | Yes | — | Your Merchant Site ID |
| `merchant_secret_key` | Yes | — | Your Merchant Secret Key |
| `base_url` | No | `https://taliuphq.com/api/v1` | API base URL |
| `timeout` | No | `10` | HTTP timeout in seconds |

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Taliup\Sdk\Client;

$client = new Client([
    'merchant_site_id'   => 'your_merchant_site_id',
    'merchant_secret_key' => 'your_merchant_secret_key',
    'base_url'           => 'https://taliuphq.com/api/v1',
]);
```

## Hosted Payments

### Create a checkout URL

Generates a hosted payment page URL. Redirect your customer to `checkout_url` to collect payment.

```php
$response = $client->hostedPayments()->createCheckoutUrl([
    // Required
    'amount'       => 49.99,

    // Recommended
    'currency'     => 'CAD',          // 'CAD' or 'USD'. Defaults to merchant's currency.
    'reference'    => 'ORDER-10029',  // Your order ID — returned in the webhook
    'redirect_url' => 'https://yoursite.com/payment/success',
    'cancel_url'   => 'https://yoursite.com/payment/cancel',

    // Optional customer info
    'first_name'   => 'Jane',
    'last_name'    => 'Doe',
    'email'        => 'jane@example.com',

    // Optional — line items
    'items' => [
        [
            'product_id' => 'SKU-001',
            'name'       => 'Widget',
            'quantity'   => 2,
            'price'      => 24.99,
            'subtotal'   => 49.98,
            'tax_total'  => 0.00,
            'sku'        => 'SKU-001',
        ],
    ],

    // Optional — webhook called after payment is captured
    'webhook_url'      => 'https://yoursite.com/webhooks/taliup',

    // Optional — tag the source for reporting
    'external_source'  => 'my-platform',

    // Optional — session expiry (5–60 minutes, default 30)
    'expires_in_minutes' => 30,
]);

$checkoutUrl = $response['checkout_url']; // Redirect customer here
$token       = $response['token'];        // Session token
$expiresAt   = $response['expires_at'];  // ISO 8601 expiry timestamp
```

> **Note:** `redirect_url`, `cancel_url`, and `webhook_url` must use HTTPS.

---

## Webhooks

After a payment is captured, Taliup sends a `POST` request to your `webhook_url` with a JSON body and an `X-Taliup-Signature` header.

### Webhook payload

```json
{
    "event":              "payment.captured",
    "merchant_site_id":   "your_merchant_site_id",
    "transaction_id":     "abc123",
    "amount":             "49.99",
    "currency":           "CAD",
    "status":             "approved",
    "card_type":          "VISA",
    "card_number_masked": "41**********1111",
    "reference":          "ORDER-10029",
    "timestamp":          "2026-05-14T18:00:00+00:00"
}
```

### Verifying the signature

Always verify the signature before processing the event. The signature is `sha256=<HMAC-SHA256>` of the raw request body, signed with your `merchant_secret_key`.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Taliup\Sdk\Exceptions\ApiException;
use Taliup\Sdk\Webhook;

$secret    = getenv('TALIUP_MERCHANT_SECRET_KEY');
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TALIUP_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent($payload, $signature, $secret);
} catch (ApiException $e) {
    http_response_code(401);
    exit($e->getMessage());
}

$reference = $event['reference'];   // your order ID
$status    = $event['status'];      // 'approved', 'declined', etc.

if ($status === 'approved') {
    // mark order as paid
}

http_response_code(200);
echo json_encode(['received' => true]);
```

Or if you just want a boolean check without throwing:

```php
$isValid = Webhook::verify($payload, $signature, $secret); // bool
```

---

## Error Handling

All errors throw `Taliup\Sdk\Exceptions\ApiException`:

```php
use Taliup\Sdk\Exceptions\ApiException;

try {
    $response = $client->hostedPayments()->createCheckoutUrl([...]);
} catch (ApiException $e) {
    $e->getMessage();     // Human-readable message
    $e->getStatusCode();  // HTTP status code (0 for connection errors)
    $e->getResponseBody(); // Raw decoded response array
}
```

---

## Testing

```bash
composer install

# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests (requires live credentials)
export TALIUP_MERCHANT_SITE_ID="your_merchant_site_id"
export TALIUP_MERCHANT_SECRET_KEY="your_merchant_secret_key"
export TALIUP_BASE_URL="https://taliuphq.com/api/v1"
./vendor/bin/phpunit --testsuite Integration
```

---

## API Reference

### `HostedPayments`

| Method | Description |
|---|---|
| `createCheckoutUrl(array $payload): array` | Create a hosted checkout session and return the URL |

### `Webhook`

| Method | Description |
|---|---|
| `Webhook::verify(string $payload, string $signature, string $secret): bool` | Verify signature, returns bool |
| `Webhook::constructEvent(string $payload, string $signature, string $secret): array` | Verify and decode payload, throws `ApiException` on failure |
