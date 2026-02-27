<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Service\EmailsLogger;

interface TransportInterface
{
    
    public function setLogger(?EmailsLogger $logger = null): void;

    public function send(EmailMessage $emailMessage): void;

    public function sendAsArray(array $emailData): void;

    public function getMessageId(): ?string;

}