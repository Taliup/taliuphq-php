<?php

namespace Taliup\Sdk\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Taliup\Sdk\Client;
use Taliup\Sdk\Exceptions\ApiException;

/**
 * Integration tests for HostedPayments API.
 * 
 * These tests make actual API calls and require valid test credentials.
 * Set the following environment variables before running:
 * - TALIUP_MERCHANT_SITE_ID
 * - TALIUP_MERCHANT_SECRET_KEY
 * - TALIUP_BASE_URL (optional, defaults to production URL)
 */
class HostedPaymentsIntegrationTest extends TestCase
{
    private ?Client $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $merchantSiteId = getenv('TALIUP_MERCHANT_SITE_ID');
        $merchantSecretKey = getenv('TALIUP_MERCHANT_SECRET_KEY');
        $baseUrl = getenv('TALIUP_BASE_URL');

        if (!$merchantSiteId || !$merchantSecretKey) {
            $this->markTestSkipped(
                'Integration tests require TALIUP_MERCHANT_SITE_ID and TALIUP_MERCHANT_SECRET_KEY environment variables.'
            );
        }

        $config = [
            'merchant_site_id' => $merchantSiteId,
            'merchant_secret_key' => $merchantSecretKey,
        ];

        if ($baseUrl) {
            $config['base_url'] = $baseUrl;
        }

        $this->client = new Client($config);
    }

    public function testCreateCheckoutUrl(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized.');
            return;
        }

        $payload = [
            'amount' => 10.00,
            'currency' => 'USD',
            'first_name' => 'Integration',
            'last_name' => 'Test',
            'reference' => 'TEST-' . time(),
            'redirect_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ];

        $response = $this->client->hostedPayments()->createCheckoutUrl($payload);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('checkout_url', $response);
        $this->assertIsString($response['checkout_url']);
        $this->assertStringStartsWith('http', $response['checkout_url']);
    }

    public function testCreateCheckoutUrlWithInvalidAmount(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized.');
            return;
        }

        $this->expectException(ApiException::class);

        $payload = [
            'amount' => -10.00, // Invalid negative amount
            'currency' => 'USD',
        ];

        $this->client->hostedPayments()->createCheckoutUrl($payload);
    }

    public function testCreateCheckoutUrlWithMissingRequiredFields(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized.');
            return;
        }

        $this->expectException(ApiException::class);

        // Missing required fields
        $payload = [];

        $this->client->hostedPayments()->createCheckoutUrl($payload);
    }

    public function testVerifyTransactionWithInvalidId(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized.');
            return;
        }

        $this->expectException(ApiException::class);

        $payload = [
            'transaction_id' => 'invalid_transaction_id_12345',
        ];

        $this->client->hostedPayments()->verifyTransaction($payload);
    }
}
