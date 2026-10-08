<?php

/**
 * Recurring checkout example.
 *
 * Creates a monthly membership. The customer enters their card once on the
 * hosted payment page and is then billed automatically every month.
 *
 * Required environment variables:
 *   TALIUP_MERCHANT_SITE_ID     — your Merchant Site ID from Taliup
 *   TALIUP_MERCHANT_SECRET_KEY  — your Merchant Secret Key from Taliup
 *   TALIUP_WEBHOOK_URL          — HTTPS URL that receives subscription.* events
 */

require __DIR__ . '/../vendor/autoload.php';

use Taliup\Sdk\Client;
use Taliup\Sdk\Exceptions\ApiException;

$merchantSiteId    = (string) getenv('TALIUP_MERCHANT_SITE_ID');
$merchantSecretKey = (string) getenv('TALIUP_MERCHANT_SECRET_KEY');
$baseUrl           = (string) getenv('TALIUP_BASE_URL');
$webhookUrl        = (string) getenv('TALIUP_WEBHOOK_URL'); // your ngrok HTTPS URL

if ($merchantSiteId === '' || $merchantSecretKey === '') {
    echo "Missing TALIUP_MERCHANT_SITE_ID or TALIUP_MERCHANT_SECRET_KEY.\n";
    exit(1);
}

if ($webhookUrl === '') {
    echo "Set TALIUP_WEBHOOK_URL to your ngrok HTTPS URL (e.g. https://xxxx.ngrok.io).\n";
    exit(1);
}

$config = ['merchant_site_id' => $merchantSiteId, 'merchant_secret_key' => $merchantSecretKey];
if ($baseUrl !== '') {
    $config['base_url'] = $baseUrl;
}

$client = new Client($config);

try {
    $response = $client->hostedPayments()->createRecurringCheckoutUrl([
        'amount'       => 49.99,
        'email'        => 'test@test.com',
        'first_name'   => 'Test',
        'last_name'    => 'User',
        'reference'    => 'MEMBER-1234',
        'description'  => 'Monthly membership',
        'redirect_url' => 'https://example.com/welcome',
        'cancel_url'   => 'https://example.com/membership',
        'webhook_url'  => $webhookUrl,
        'recurring'    => [
            'billing_cycle' => 'MONTHLY',

            // Charge today by default. To start later, set a future date (YYYY-MM-DD);
            // the card is saved at checkout and nothing is charged until then.
            // 'first_payment_date' => '2026-11-01',

            // Set with a future first_payment_date to offer a free trial until that date.
            // 'has_free_trial' => true,

            // Omit to bill until the subscription is cancelled.
            // 'end_date' => '2027-10-31',
        ],
    ]);
} catch (ApiException $e) {
    echo "Error {$e->getStatusCode()}: {$e->getMessage()}\n";
    exit(1);
}

echo "Checkout URL: " . $response['checkout_url'] . "\n";
echo "Token:        " . $response['token'] . "\n";
echo "Expires at:   " . $response['expires_at'] . "\n";
echo "Subscription: " . json_encode($response['subscription'], JSON_PRETTY_PRINT) . "\n";
echo "\nOpen the checkout URL in a browser and complete a test signup.\n";
echo "Watch the webhook-receiver terminal for subscription.activated.\n";
