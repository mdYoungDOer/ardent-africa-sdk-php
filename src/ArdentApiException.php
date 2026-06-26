<?php

declare(strict_types=1);

namespace Ardent\Sdk;

/**
 * Thrown for any non-2xx response from the Ardent public API, carrying the error
 * envelope fields (machine-readable code, HTTP status, and support reference).
 */
class ArdentApiException extends \Exception
{
    public string $apiCode;
    public int $httpStatus;
    public ?string $ref;

    public function __construct(string $message, string $apiCode = 'INTERNAL', int $httpStatus = 0, ?string $ref = null)
    {
        parent::__construct($message);
        $this->apiCode    = $apiCode;
        $this->httpStatus = $httpStatus;
        $this->ref        = $ref;
    }
}
