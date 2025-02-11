<?php

namespace Solo\RequestGuard\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors = [], ?Exception $previous = null)
    {
        $message = "Validation failed";
        $code = 422;

        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}