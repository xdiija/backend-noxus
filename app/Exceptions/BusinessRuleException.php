<?php

namespace App\Exceptions;

use Exception;

/**
 * A violation of a domain/business rule (e.g. deleting a Role that still has
 * users, or deleting your own account). Rendered centrally in Handler::render()
 * as a JSON message with the given HTTP status (422 by default).
 */
class BusinessRuleException extends Exception
{
    public function __construct(string $message, protected int $status = 422)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }
}
