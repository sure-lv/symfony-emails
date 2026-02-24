<?php

namespace SureLv\Emails\Exception\Sender;

interface SenderSendExceptionInterface
{
    
    public function shouldRetry(): bool;

}