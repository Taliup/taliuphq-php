<?php

namespace Taliup\Sdk\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use Taliup\Sdk\Client;
use Taliup\Sdk\Exceptions\ApiException;
use Taliup\Sdk\Resources\HostedPayments;

class ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testConstructorWithValidConfig(): void
    {
        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithCustomBaseUrl(): void
    {
        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
            'base_url' => 'https://custom.taliup.com/api',
        ]);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithCustomTimeout(): void
    {
        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
            'timeout' => 30,
        ]);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorThrowsExceptionWhenMissingMerchantSiteId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing merchant credentials.');

        new Client([
            'merchant_secret_key' => 'test_secret_key',
        ]);
    }

    public function testConstructorThrowsExceptionWhenMissingMerchantSecretKey(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing merchant credentials.');

        new Client([
            'merchant_site_id' => 'test_site_id',
        ]);
    }

    public function testConstructorThrowsExceptionWhenEmptyMerchantSiteId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing merchant credentials.');

        new Client([
            'merchant_site_id' => '',
            'merchant_secret_key' => 'test_secret_key',
        ]);
    }

    public function testConstructorThrowsExceptionWhenEmptyMerchantSecretKey(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing merchant credentials.');

        new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => '',
        ]);
    }

    public function testHostedPaymentsReturnsHostedPaymentsInstance(): void
    {
        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $hostedPayments = $client->hostedPayments();

        $this->assertInstanceOf(HostedPayments::class, $hostedPayments);
    }

    public function testPostMethodWithSuccessfulResponse(): void
    {
        $mockGuzzle = Mockery::mock(GuzzleClient::class);
        $mockResponse = new Response(200, [], json_encode([
            'success' => true,
            'checkout_url' => 'https://checkout.taliup.com/abc123',
        ]));

        $mockGuzzle->shouldReceive('post')
            ->once()
            ->with('test-endpoint', Mockery::type('array'))
            ->andReturn($mockResponse);

        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        // Use reflection to inject mock Guzzle client
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockGuzzle);

        $result = $client->post('/test-endpoint', ['test' => 'data']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('https://checkout.taliup.com/abc123', $result['checkout_url']);
    }

    public function testPostMethodThrowsExceptionOnNon2xxResponse(): void
    {
        $mockGuzzle = Mockery::mock(GuzzleClient::class);
        $mockResponse = new Response(400, [], json_encode([
            'message' => 'Invalid request',
        ]));

        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockGuzzle);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid request');

        $client->post('/test-endpoint', ['test' => 'data']);
    }

    public function testPostMethodThrowsExceptionOnGuzzleException(): void
    {
        $mockGuzzle = Mockery::mock(GuzzleClient::class);
        $request = new Request('POST', '/test-endpoint');
        $exception = new RequestException('Connection failed', $request);

        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockGuzzle);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Request failed: Connection failed');

        $client->post('/test-endpoint', ['test' => 'data']);
    }

    public function testPostMethodWith500Response(): void
    {
        $mockGuzzle = Mockery::mock(GuzzleClient::class);
        $mockResponse = new Response(500, [], json_encode([
            'message' => 'Internal server error',
        ]));

        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockGuzzle);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Internal server error');

        $client->post('/test-endpoint', ['test' => 'data']);
    }

    public function testPostMethodWithEmptyResponse(): void
    {
        $mockGuzzle = Mockery::mock(GuzzleClient::class);
        $mockResponse = new Response(200, [], '');

        $mockGuzzle->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $client = new Client([
            'merchant_site_id' => 'test_site_id',
            'merchant_secret_key' => 'test_secret_key',
        ]);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockGuzzle);

        $result = $client->post('/test-endpoint', ['test' => 'data']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
