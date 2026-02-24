<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Entity\EmailMessage;

abstract class AbstractTransport implements TransportInterface
{
    
    protected ?string $messageId = null;

    public function __construct() {}

    abstract public function send(EmailMessage $emailMessage): void;

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

}