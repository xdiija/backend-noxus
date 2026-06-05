<?php

namespace App\Exceptions\User;

use Exception;

class InvalidPasswordChangeException extends Exception
{
    public function __construct(
        public readonly string $field,
        string $message
    ) {
        parent::__construct($message);
    }
}
