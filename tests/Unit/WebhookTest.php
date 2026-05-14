<?php

namespace Taliup\Sdk\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Taliup\Sdk\Exceptions\ApiException;
use Taliup\Sdk\Webhook;

class WebhookTest extends TestCase
{
    private string $secret = 'hpsk_testsecretkey123456';

    private function makeSignature(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->secret);
    }

    public function testVerifyReturnsTrueForValidSignature(): void
    {
        $payload = json_encode(['event' => 'payment.approved', 'amount' => '10.00']);
        $signature = $this->makeSignature($payload);

        $this->assertTrue(Webhook::verify($payload, $signature, $this->secret));
    }

    public function testVerifyReturnsFalseForTamperedPayload(): void
    {
        $payload = json_encode(['event' => 'payment.approved', 'amount' => '10.00']);
        $signature = $this->makeSignature($payload);
        $tampered = json_encode(['event' => 'payment.approved', 'amount' => '0.01']);

        $this->assertFalse(Webhook::verify($tampered, $signature, $this->secret));
    }

    public function testVerifyReturnsFalseForWrongSecret(): void
    {
        $payload = json_encode(['event' => 'payment.approved']);
        $signature = $this->makeSignature($payload);

        $this->assertFalse(Webhook::verify($payload, $signature, 'wrong_secret'));
    }

    public function testVerifyReturnsFalseForMissingPrefix(): void
    {
        $payload = json_encode(['event' => 'payment.approved']);
        $rawHmac = hash_hmac('sha256', $payload, $this->secret);

        $this->assertFalse(Webhook::verify($payload, $rawHmac, $this->secret));
    }

    public function testVerifyReturnsFalseForEmptyPayload(): void
    {
        $this->assertFalse(Webhook::verify('', 'sha256=abc', $this->secret));
    }

    public function testVerifyReturnsFalseForEmptySignature(): void
    {
        $this->assertFalse(Webhook::verify('{"event":"test"}', '', $this->secret));
    }

    public function testVerifyReturnsFalseForEmptySecret(): void
    {
        $this->assertFalse(Webhook::verify('{"event":"test"}', 'sha256=abc', ''));
    }

    public function testConstructEventReturnsDecodedPayload(): void
    {
        $data = ['event' => 'payment.approved', 'reference' => 'ORDER-123', 'amount' => '99.00'];
        $payload = json_encode($data);
        $signature = $this->makeSignature($payload);

        $result = Webhook::constructEvent($payload, $signature, $this->secret);

        $this->assertEquals($data, $result);
    }

    public function testConstructEventThrowsOnInvalidSignature(): void
    {
        $payload = json_encode(['event' => 'payment.approved']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Webhook signature verification failed.');

        Webhook::constructEvent($payload, 'sha256=invalidsignature', $this->secret);
    }

    public function testConstructEventThrowsOnInvalidJson(): void
    {
        $invalidJson = 'not-json';
        $signature = $this->makeSignature($invalidJson);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Webhook payload is not valid JSON.');

        Webhook::constructEvent($invalidJson, $signature, $this->secret);
    }
}
