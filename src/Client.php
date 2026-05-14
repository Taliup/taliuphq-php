<?php

namespace Taliup\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Taliup\Sdk\Exceptions\ApiException;
use Taliup\Sdk\Resources\HostedPayments;

class Client
{
    private string $merchantSiteId;
    private string $merchantSecretKey;
    private string $baseUrl;
    private int $timeout;
    private GuzzleClient $http;

    public function __construct(array $config)
    {
        $this->merchantSiteId = $config['merchant_site_id'] ?? '';
        $this->merchantSecretKey = $config['merchant_secret_key'] ?? '';
        $baseUrl = $config['base_url'] ?? 'https://taliuphq.com/api/v1';
        $this->baseUrl = rtrim((string) $baseUrl, '/');
        $this->timeout = (int) ($config['timeout'] ?? 10);

        if ($this->merchantSiteId === '' || $this->merchantSecretKey === '') {
            throw new ApiException('Missing merchant credentials.', 0);
        }

        if ($this->baseUrl === '') {
            throw new ApiException('Missing base_url.', 0);
        }

        $this->http = new GuzzleClient([
            'base_uri'    => $this->baseUrl . '/',
            'timeout'     => $this->timeout,
            'http_errors' => false, // Handle HTTP errors ourselves so we can extract body/status
        ]);
    }

    public function hostedPayments(): HostedPayments
    {
        return new HostedPayments($this);
    }

    public function post(string $path, array $payload): array
    {
        try {
            $response = $this->http->post(ltrim($path, '/'), [
                'headers' => $this->authHeaders(),
                'json' => $payload,
            ]);
        } catch (GuzzleException $exception) {
            throw new ApiException('Request failed: ' . $exception->getMessage(), 0);
        }

        return $this->parseResponse($response);
    }

    private function authHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Merchant-Site-Id' => $this->merchantSiteId,
            'X-Merchant-Secret-Key' => $this->merchantSecretKey,
        ];
    }

    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = $body !== '' ? json_decode($body, true) : [];

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = is_array($decoded) && isset($decoded['message'])
                ? (string) $decoded['message']
                : 'API request failed.';

            throw new ApiException($message, $statusCode, is_array($decoded) ? $decoded : []);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
