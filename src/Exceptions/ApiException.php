<?php

namespace Taliup\Sdk\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    private int $statusCode;
    private array $responseBody;

    public function __construct(string $message, int $statusCode, array $responseBody = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
