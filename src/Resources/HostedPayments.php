<?php

namespace Taliup\Sdk\Resources;

use Taliup\Sdk\Client;

class HostedPayments
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createCheckoutUrl(array $payload): array
    {
        return $this->client->post('/hosted-payments/checkout-url', $payload);
    }

    /**
     * Enroll the customer in a recurring payment. Requires the Recurring Payments
     * feature on the merchant account.
     */
    public function createRecurringCheckoutUrl(array $payload): array
    {
        return $this->client->post('/hosted-payments/recurring-checkout-url', $payload);
    }

    public function verifyTransaction(array $payload): array
    {
        return $this->client->post('/hosted-payments/verify-transaction', $payload);
    }
}
