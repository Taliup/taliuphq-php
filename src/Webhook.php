<?php

namespace Taliup\Sdk;

use Taliup\Sdk\Exceptions\ApiException;

class Webhook
{
    /**
     * Verify the X-Taliup-Signature header against the raw request body.
     *
     * @param string $payload   Raw request body (file_get_contents('php://input'))
     * @param string $signature Value of the X-Taliup-Signature header
     * @param string $secret    Your Merchant Secret Key
     */
    public static function verify(string $payload, string $signature, string $secret): bool
    {
        if ($payload === '' || $signature === '' || $secret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify and decode a webhook payload.
     * Throws ApiException if the signature is invalid.
     *
     * @param string $payload   Raw request body (file_get_contents('php://input'))
     * @param string $signature Value of the X-Taliup-Signature header
     * @param string $secret    Your Merchant Secret Key
     * @return array            Decoded JSON payload
     *
     * @throws ApiException
     */
    public static function constructEvent(string $payload, string $signature, string $secret): array
    {
        if (!static::verify($payload, $signature, $secret)) {
            throw new ApiException('Webhook signature verification failed.', 401);
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw new ApiException('Webhook payload is not valid JSON.', 400);
        }

        return $decoded;
    }
}
