<?php

namespace SureLv\Emails\Message;

final class SendEmailMessage
{
    
    /**
     * Constructor
     * 
     * @param int $emailMessageId
     * @param array<string, mixed> $params
     */
    public function __construct(
        private int $emailMessageId,
        private array $params = []
    ) {}

    public function getEmailMessageId(): int
    {
        return $this->emailMessageId;
    }

    /**
     * Get params
     * 
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

}
