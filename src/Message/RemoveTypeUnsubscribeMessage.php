<?php

namespace SureLv\Emails\Message;

final class RemoveTypeUnsubscribeMessage
{

    public function __construct(
        private ?int $contactId,
        private ?string $email,
        private string $scopeType,
        private int $scopeId,
        private string $emailType,
    ) { }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }
    
    public function getEmailType(): string
    {
        return $this->emailType;
    }
    
}