<?php

namespace SureLv\Emails\Exception\Sender;

use Exception;
 
class SenderSendException extends Exception implements SenderSendExceptionInterface
{

    public function __construct(string $message = '', \Throwable $previous = null, private bool $shouldRetry = false)
    {
        parent::__construct($message, 0, $previous);
    }

    public function shouldRetry(): bool
    {
        return $this->shouldRetry;
    }

}