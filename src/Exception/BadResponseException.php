<?php

namespace App\Exception;

use App\Exception\WhopException;

class BadResponseException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Bad response: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}
