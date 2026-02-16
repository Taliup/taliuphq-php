<?php

namespace Taliup\Sdk\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Taliup\Sdk\Exceptions\ApiException;

class ApiExceptionTest extends TestCase
{
    public function testExceptionCanBeCreatedWithMessage(): void
    {
        $exception = new ApiException('Test error message', 400);

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals(400, $exception->getCode());
    }

    public function testExceptionCanBeCreatedWithResponseBody(): void
    {
        $responseBody = [
            'error' => 'invalid_request',
            'message' => 'Missing required field',
        ];

        $exception = new ApiException('API Error', 422, $responseBody);

        $this->assertEquals('API Error', $exception->getMessage());
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals($responseBody, $exception->getResponseBody());
    }

    public function testExceptionCanBeCreatedWithoutResponseBody(): void
    {
        $exception = new ApiException('Simple error', 500);

        $this->assertEquals('Simple error', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertIsArray($exception->getResponseBody());
        $this->assertEmpty($exception->getResponseBody());
    }

    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new ApiException('Error', 400);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Test exception');
        $this->expectExceptionCode(404);

        throw new ApiException('Test exception', 404);
    }

    public function testExceptionWithZeroStatusCode(): void
    {
        $exception = new ApiException('Connection error', 0);

        $this->assertEquals('Connection error', $exception->getMessage());
        $this->assertEquals(0, $exception->getStatusCode());
    }

    public function testExceptionWithComplexResponseBody(): void
    {
        $responseBody = [
            'error' => [
                'code' => 'PAYMENT_DECLINED',
                'message' => 'Card was declined',
                'details' => [
                    'reason' => 'insufficient_funds',
                    'decline_code' => 'card_declined',
                ],
            ],
            'transaction_id' => 'txn_12345',
        ];

        $exception = new ApiException('Payment failed', 402, $responseBody);

        $this->assertEquals(402, $exception->getStatusCode());
        $this->assertEquals($responseBody, $exception->getResponseBody());
        $this->assertEquals('txn_12345', $exception->getResponseBody()['transaction_id']);
    }

    public function testGetStatusCodeReturnsInteger(): void
    {
        $exception = new ApiException('Error', 400);

        $this->assertIsInt($exception->getStatusCode());
        $this->assertSame(400, $exception->getStatusCode());
    }

    public function testGetResponseBodyReturnsArray(): void
    {
        $exception = new ApiException('Error', 400, ['key' => 'value']);

        $this->assertIsArray($exception->getResponseBody());
        $this->assertEquals(['key' => 'value'], $exception->getResponseBody());
    }
}
