<?php

namespace App\Exception;

use App\Exception\WhopException;

class InvalidLicenseKeyException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Invalid license key: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}