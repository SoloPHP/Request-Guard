<?php

namespace Solo\RequestGuard\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(string $message = "Access denied", int $code = 403, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}