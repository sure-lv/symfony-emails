<?php

namespace SureLv\Emails\Transport;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Service\EmailsLogger;

abstract class AbstractTransport implements TransportInterface
{
    
    protected ?EmailsLogger $logger = null;

    protected ?string $messageId = null;

    public function __construct()
    {
    }

    public function setLogger(?EmailsLogger $logger = null): void
    {
        $this->logger = $logger;
    }

    public function send(EmailMessage $emailMessage): void
    {
        $emailData = $this->convertEmailMessageToArray($emailMessage);
        $this->sendAsArray($emailData);
    }

    public function sendAsArray(array $emailData): void
    {
        $this->messageId = $this->processSend($emailData);
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }


    /**
     * 
     * PROTECTED METHODS
     * 
     */


    protected function processSend(array $emailData): ?string
    {
        throw new \Exception('Transport processSend method not implemented');
        return null;
    }

    protected function convertEmailMessageToArray(EmailMessage $emailMessage): array
    {
        $emailData = [];
        $emailData['from'] = $emailMessage->getFromEmail();
        $emailData['to'] = $emailMessage->getToEmail();
        $emailData['subject'] = $emailMessage->getSubject();
        $emailData['html'] = $emailMessage->getBodyHtml();
        $emailData['text'] = $emailMessage->getBodyPlain();
        $emailData['reply_to'] = $emailMessage->getReplyTo();
        $emailData['headers'] = $emailMessage->getHeaders() ?? [];

        return $emailData;
    }

}