# TaliupHQ PHP SDK

Official PHP SDK for the TaliupHQ API.

## Install

```bash
composer require taliup/taliuphq-php
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

### Create a recurring checkout URL

Enrolls a customer in a recurring payment, such as a monthly membership. Redirect the customer to `checkout_url`. Once they complete checkout, Taliup charges them on the schedule you set and sends `subscription.*` webhooks to your `webhook_url`.

Requires the Recurring Payments feature on the merchant account.

```php
$response = $client->hostedPayments()->createRecurringCheckoutUrl([
    // Required
    'amount'    => 49.99,                 // charged every cycle
    'email'     => 'jane@example.com',    // finds or creates the customer in Taliup HQ
    'recurring' => [
        'billing_cycle'      => 'MONTHLY',     // DAILY, WEEKLY, BIWEEKLY, MONTHLY or ANNUALLY
        'first_payment_date' => '2026-10-22',  // optional, YYYY-MM-DD, defaults to today
        'end_date'           => null,          // optional, YYYY-MM-DD, after first_payment_date
        'has_free_trial'     => true,          // optional, needs a first_payment_date after today
    ],

    // Recommended
    'reference'    => 'MEMBER-1042',          // your ID — returned in every webhook
    'description'  => 'Monthly membership',   // line item name when no items are sent
    'redirect_url' => 'https://yoursite.com/welcome',
    'cancel_url'   => 'https://yoursite.com/join',
    'webhook_url'  => 'https://yoursite.com/webhooks/taliup',

    // Optional customer info
    'first_name'   => 'Jane',
    'last_name'    => 'Doe',
    'phone'        => '+15145550100',

    // Optional — line items. amount must equal price × quantity plus tax_total.
    'items' => [
        ['name' => 'Membership', 'quantity' => 1, 'price' => 43.48, 'tax_total' => 6.51],
    ],
]);

$checkoutUrl  = $response['checkout_url'];   // Redirect customer here
$subscription = $response['subscription'];   // id, status ('PENDING'), reference, amount, currency, billing_cycle, ...
```

How the first charge works:

- **`first_payment_date` is today, or omitted:** the customer is charged at checkout. The next charge is one billing cycle later.
- **`first_payment_date` is in the future:** the card is saved at checkout and nothing is charged. The first charge happens on `first_payment_date`.
- **`trial_days` is set:** a free trial. Taliup works out the first charge date as that many days from today, so you don't send `first_payment_date` or `has_free_trial`.

The agreement is billed in the merchant location's currency. Treat the webhook as the source of truth: the redirect to `redirect_url` is for the customer experience only, and its query parameters do not reflect a free trial.

### Memberships

Add `membership` to the same call to sign the customer up to a membership in Taliup HQ, with the included services and discounts the business set for the plan. Requires the Memberships feature.

```php
$response = $client->hostedPayments()->createRecurringCheckoutUrl([
    'amount'     => 15.99,
    'email'      => 'jane@example.com',
    'recurring'  => ['billing_cycle' => 'MONTHLY'],
    'membership' => [
        'code' => 'gold',            // your own name for the plan
        'name' => 'Gold membership', // shown to the customer and in Taliup HQ
    ],
    'reference'    => 'MEMBER-1042',
    'redirect_url' => 'https://yoursite.com/welcome',
    'webhook_url'  => 'https://yoursite.com/webhooks/taliup',
]);

$membership = $response['membership'];   // id, code, name
```

How plans are matched:

- You never copy an ID out of Taliup HQ. `code` is a name you choose and keep in your own site (letters, digits, dots, dashes and underscores, up to 64 characters).
- Taliup finds the plan by `code` and `billing_cycle`. The first sign-up creates the plan; later sign-ups reuse it. One code can have a monthly and a yearly price.
- A different `amount` for the same code and cycle updates the plan's price. Members who already signed up keep the price they joined at.
- A plan deleted or switched off in Taliup HQ comes back on the next sign-up.
- Name, price and cycle come from your site. What members get (included services, discounts) is set in Taliup HQ, under Memberships.

Things to know:

- A membership runs until it is cancelled, so leave out `end_date`. For a free trial, send `recurring.trial_days`: the member has their benefits from sign-up and is first charged that many days later.
- A customer who already has this membership gets a `422`. A sign-up they started and never paid for is replaced by the new one.
- Every `subscription.*` webhook carries the same `membership` object. It is `null` when the checkout was created without one.

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

### Subscription webhooks

Agreements created with `createRecurringCheckoutUrl()` send these events to their `webhook_url`, signed the same way:

| Event | Sent when |
|---|---|
| `subscription.activated` | The customer completed checkout and the schedule is active |
| `subscription.payment_captured` | A renewal was charged |
| `subscription.payment_declined` | A renewal was declined. The agreement becomes `PAST_DUE` |
| `subscription.cancelled` | The merchant, the customer or Taliup cancelled the agreement |
| `subscription.completed` | The agreement reached its `end_date` |

Renewal results arrive once a day, after the merchant's processor settles. They are not real time.

```json
{
    "event":            "subscription.activated",
    "merchant_site_id": "your_merchant_site_id",
    "reference":        "MEMBER-1042",
    "membership":       { "id": 57, "code": "gold", "name": "Gold membership" },
    "subscription": {
        "id":                 318,
        "status":             "ACTIVE",
        "amount":             49.99,
        "currency":           "CAD",
        "billing_cycle":      "MONTHLY",
        "has_free_trial":     false,
        "first_payment_date": "2026-10-08",
        "next_payment_date":  "2026-11-08",
        "end_date":           null,
        "card_type":          "VISA",
        "card_number_masked": "41**********1111",
        "cancelled_by":       null
    },
    "transaction": {
        "transaction_id":     "abc123",
        "amount":             49.99,
        "currency":           "CAD",
        "status":             "CAPTURED",
        "card_type":          "VISA",
        "card_number_masked": "41**********1111"
    },
    "timestamp": "2026-10-08T15:42:10+00:00"
}
```

`transaction` is `null` when nothing was charged: on `subscription.activated` for a free trial or future start, and on `subscription.cancelled` and `subscription.completed`. `cancelled_by` is `merchant`, `client` or `system` on `subscription.cancelled`.

```php
$event = Webhook::constructEvent($payload, $signature, $secret);

switch ($event['event']) {
    case 'subscription.activated':
    case 'subscription.payment_captured':
        // keep the membership for $event['reference'] active until $event['subscription']['next_payment_date']
        break;
    case 'subscription.payment_declined':
    case 'subscription.cancelled':
    case 'subscription.completed':
        // follow up or end the membership for $event['reference']
        break;
}
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
| `createRecurringCheckoutUrl(array $payload): array` | Create a recurring checkout session and return the URL and the pending subscription |

### `Webhook`

| Method | Description |
|---|---|
| `Webhook::verify(string $payload, string $signature, string $secret): bool` | Verify signature, returns bool |
| `Webhook::constructEvent(string $payload, string $signature, string $secret): array` | Verify and decode payload, throws `ApiException` on failure |
