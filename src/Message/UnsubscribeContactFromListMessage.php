<?php

namespace SureLv\Emails\Message;

final class UnsubscribeContactFromListMessage
{
    
    public function __construct(
        private int $contactId,
        private ?int $listId,
        private ?string $listName,
        private string $scopeType,
        private ?int $scopeId,
        private ?\DateTime $unsubscribedAt = null,
    ) { }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getListId(): ?int
    {
        return $this->listId;
    }

    public function getListName(): ?string
    {
        return $this->listName;
    }

    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    public function getScopeId(): ?int
    {
        return $this->scopeId;
    }

    public function getUnsubscribedAt(): ?\DateTime
    {
        return $this->unsubscribedAt;
    }
}
