<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Entity\EmailMessage;

interface TransportInterface
{
    
    public function send(EmailMessage $emailMessage): void;

    public function getMessageId(): ?string;

}