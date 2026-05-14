<?php

require __DIR__ . '/../vendor/autoload.php';

use Taliup\Sdk\Client;

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

$response = $client->hostedPayments()->createCheckoutUrl([
    'amount'       => 15.00,
    'currency'     => 'CAD',
    'first_name'   => 'Test',
    'last_name'    => 'User',
    'email'        => 'test@test.com',
    'reference'    => 'ORDER-WEBHOOK-TEST',
    'redirect_url' => 'https://example.com/success',
    'cancel_url'   => 'https://example.com/cancel',
    'webhook_url'  => $webhookUrl,
]);

echo "Checkout URL: " . $response['checkout_url'] . "\n";
echo "Token:        " . $response['token'] . "\n";
echo "Expires at:   " . $response['expires_at'] . "\n";
echo "\nOpen the checkout URL in a browser and complete a test payment.\n";
echo "Watch the webhook-receiver terminal for the incoming webhook.\n";
