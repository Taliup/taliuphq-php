<?php

require __DIR__ . '/../vendor/autoload.php';

use Taliup\Sdk\Client;

$merchantSiteId = (string) getenv('TALIUP_MERCHANT_SITE_ID');
$merchantSecretKey = (string) getenv('TALIUP_MERCHANT_SECRET_KEY');
$baseUrl = (string) getenv('TALIUP_BASE_URL');

if ($merchantSiteId === '' || $merchantSecretKey === '') {
    echo "Missing TALIUP_MERCHANT_SITE_ID or TALIUP_MERCHANT_SECRET_KEY.\n";
    exit(1);
}

$config = [
    'merchant_site_id' => $merchantSiteId,
    'merchant_secret_key' => $merchantSecretKey,
];

if ($baseUrl !== '') {
    $config['base_url'] = $baseUrl;
}

$client = new Client($config);

$response = $client->hostedPayments()->createCheckoutUrl([
    'amount' => 10.00,
    'currency' => 'CAD',
    'first_name' => 'Test',
    'last_name' => 'User',
    'redirect_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
]);

print_r($response);
