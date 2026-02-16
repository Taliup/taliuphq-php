<?php

namespace Taliup\Sdk\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Taliup\Sdk\Client;
use Taliup\Sdk\Resources\HostedPayments;

class HostedPaymentsTest extends TestCase
{
    private HostedPayments $hostedPayments;
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(Client::class);
        $this->hostedPayments = new HostedPayments($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateCheckoutUrlCallsClientPostWithCorrectEndpoint(): void
    {
        $payload = [
            'amount' => 99.99,
            'currency' => 'USD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'redirect_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ];

        $expectedResponse = [
            'success' => true,
            'checkout_url' => 'https://checkout.taliup.com/session123',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/checkout-url', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->createCheckoutUrl($payload);

        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals('https://checkout.taliup.com/session123', $result['checkout_url']);
    }

    public function testCreateCheckoutUrlWithMinimalPayload(): void
    {
        $payload = [
            'amount' => 10.00,
            'currency' => 'CAD',
        ];

        $expectedResponse = [
            'success' => true,
            'checkout_url' => 'https://checkout.taliup.com/minimal',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/checkout-url', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->createCheckoutUrl($payload);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testCreateCheckoutUrlWithAllOptionalFields(): void
    {
        $payload = [
            'amount' => 149.99,
            'currency' => 'EUR',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'reference' => 'ORDER-12345',
            'redirect_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'metadata' => [
                'customer_id' => 'CUST-789',
                'order_date' => '2026-02-15',
            ],
        ];

        $expectedResponse = [
            'success' => true,
            'checkout_url' => 'https://checkout.taliup.com/complete',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/checkout-url', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->createCheckoutUrl($payload);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testVerifyTransactionCallsClientPostWithCorrectEndpoint(): void
    {
        $payload = [
            'transaction_id' => 'txn_1234567890',
        ];

        $expectedResponse = [
            'success' => true,
            'status' => 'completed',
            'amount' => 99.99,
            'currency' => 'USD',
            'transaction_id' => 'txn_1234567890',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/verify-transaction', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->verifyTransaction($payload);

        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals('completed', $result['status']);
    }

    public function testVerifyTransactionWithPendingStatus(): void
    {
        $payload = [
            'transaction_id' => 'txn_pending',
        ];

        $expectedResponse = [
            'success' => true,
            'status' => 'pending',
            'transaction_id' => 'txn_pending',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/verify-transaction', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->verifyTransaction($payload);

        $this->assertEquals('pending', $result['status']);
    }

    public function testVerifyTransactionWithFailedStatus(): void
    {
        $payload = [
            'transaction_id' => 'txn_failed',
        ];

        $expectedResponse = [
            'success' => false,
            'status' => 'failed',
            'transaction_id' => 'txn_failed',
            'error_message' => 'Payment declined',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/verify-transaction', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->verifyTransaction($payload);

        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Payment declined', $result['error_message']);
    }

    public function testVerifyTransactionWithAdditionalFields(): void
    {
        $payload = [
            'transaction_id' => 'txn_1234567890',
            'reference' => 'ORDER-12345',
        ];

        $expectedResponse = [
            'success' => true,
            'status' => 'completed',
            'transaction_id' => 'txn_1234567890',
            'reference' => 'ORDER-12345',
        ];

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/hosted-payments/verify-transaction', $payload)
            ->andReturn($expectedResponse);

        $result = $this->hostedPayments->verifyTransaction($payload);

        $this->assertEquals($expectedResponse, $result);
    }
}
