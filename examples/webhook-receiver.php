<?php

/**
 * Webhook receiver example.
 *
 * Deploy this file on your server and set its URL as the webhook_url
 * when creating a checkout session.
 *
 * Required environment variable:
 *   TALIUP_MERCHANT_SECRET_KEY  — your Merchant Secret Key from Taliup
 */

require __DIR__ . '/../vendor/autoload.php';

use Taliup\Sdk\Exceptions\ApiException;
use Taliup\Sdk\Webhook;

$secret = (string) getenv('TALIUP_MERCHANT_SECRET_KEY');

if ($secret === '') {
    http_response_code(500);
    exit('Missing TALIUP_MERCHANT_SECRET_KEY.');
}

$payload   = (string) file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TALIUP_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent($payload, $signature, $secret);
} catch (ApiException $e) {
    http_response_code(401);
    exit($e->getMessage());
}

// $event is the decoded JSON payload, e.g.:
// [
//   'event'              => 'payment.captured',
//   'merchant_site_id'   => 'hpm_xxxxx',
//   'transaction_id'     => '8110000000033493479',
//   'amount'             => 15,
//   'currency'           => 'CAD',
//   'status'             => 'CAPTURED',
//   'card_type'          => null,
//   'card_number_masked' => '4****0002',
//   'reference'          => 'ORDER-123',
//   'timestamp'          => '2026-06-25T16:07:50+00:00',
// ]

$reference = $event['reference'] ?? null;
$status    = $event['status'] ?? null;

if ($status === 'CAPTURED') {
    // TODO: mark the order as paid in your system using $reference
    error_log("Payment captured for order: {$reference}");
} else {
    // TODO: handle declined/failed payment
    error_log("Payment not captured for order: {$reference} (status: {$status})");
}

http_response_code(200);
echo json_encode(['received' => true]);
