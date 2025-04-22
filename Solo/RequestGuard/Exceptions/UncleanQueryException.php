<?php

namespace Solo\RequestGuard\Exceptions;

class UncleanQueryException extends \RuntimeException
{
    public function __construct(
        public readonly array $cleanedParams,
        public readonly string $redirectUri,
        string $message = 'Query parameters require cleaning.'
    ) {
        parent::__construct($message, 302);
    }
}