<?php

namespace App\Exception;

use App\Exception\WhopException;

class BadStatusCodeException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Bad status code: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}