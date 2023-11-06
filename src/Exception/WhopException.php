<?php

namespace App\Exception;

class WhopException extends \Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        // some code

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class InvalidLicenseKeyException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Invalid license key: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}
class BadStatusCodeException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Bad status code: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}
class BadResponseException extends WhopException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        $customMessage = "Bad response: {$message}";

        parent::__construct($customMessage, $code, $previous);
    }
}
