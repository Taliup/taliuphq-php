# Taliup PHP SDK

Official PHP library for the TaliupHQ API.

## Description

The Taliup PHP library offers easy integration with the TaliupHQ API for PHP applications. It features a comprehensive set of resource classes that automatically adapt to API responses, ensuring seamless compatibility across different versions of the TaliupHQ API.

## Install

```bash
composer require taliup/taliup-php
```

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Taliup\Sdk\Client;

$client = new Client([
    'merchant_site_id' => 'hpm_xxxxx',
    'merchant_secret_key' => 'hpsk_xxxxx',
    'base_url' => 'https://taliuphq.com/api/v1',
]);

$response = $client->hostedPayments()->createCheckoutUrl([
    'amount' => 49.99,
    'currency' => 'USD',
    'first_name' => 'Jane',
    'last_name' => 'Doe',
    'reference' => 'ORDER-10029',
    'redirect_url' => 'https://merchant-site.com/payment/success',
    'cancel_url' => 'https://merchant-site.com/payment/cancel',
]);

$checkoutUrl = $response['checkout_url'] ?? null;
```

## Verify Transaction

```php
$response = $client->hostedPayments()->verifyTransaction([
    'transaction_id' => '1234567890',
]);
```

## Configuration

- `merchant_site_id` (required)
- `merchant_secret_key` (required)
- `base_url` (optional, default `https://taliuphq.com/api/v1`)
- `timeout` (optional, default 10 seconds)

## Error Handling

The SDK throws `Taliup\Sdk\Exceptions\ApiException` for non-2xx responses.

## API Reference

Hosted Payments:
- `createCheckoutUrl(array $payload)`
- `verifyTransaction(array $payload)`
