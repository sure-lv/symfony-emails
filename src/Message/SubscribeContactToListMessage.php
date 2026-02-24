<?php

namespace SureLv\Emails\Message;

use SureLv\Emails\Enum\ListMemberStatus;

final class SubscribeContactToListMessage
{
    
    /**
     * Create from email and list name
     * 
     * @param string $email
     * @param string $listName
     * @param string $scopeType
     * @param ?int $scopeId
     * @param ListMemberStatus $status
     * @param ?string $source
     * @param array<string, mixed> $params
     * @param ?array<string, mixed> $data
     * @param ?\DateTime $subscribedAt
     * @param ?\DateTime $unsubscribedAt
     * @return self
     */
    public static function createFromEmailAndListName(string $email, string $listName, string $scopeType, ?int $scopeId, ListMemberStatus $status = ListMemberStatus::SUBSCRIBED, ?string $source = null, array $params = [], ?array $data = null, ?\DateTime $subscribedAt = null, ?\DateTime $unsubscribedAt = null): self
    {
        return new self(null, $email, null, $listName, $scopeType, $scopeId, $status, $source, $params, $data, $subscribedAt, $unsubscribedAt);
    }

    /**
     * Constructor
     * 
     * @param int|null $contactId
     * @param string|null $email
     * @param ?int $listId
     * @param ?string $listName
     * @param string $scopeType
     * @param ?int $scopeId
     * @param ListMemberStatus $status
     * @param ?string $source
     * @param array<string, mixed> $params
     * @param ?array<string, mixed> $data
     * @param ?\DateTime $subscribedAt
     * @param ?\DateTime $unsubscribedAt
     */
    public function __construct(
        private ?int $contactId,
        private ?string $email,
        private ?int $listId,
        private ?string $listName,
        private string $scopeType,
        private ?int $scopeId,
        private ListMemberStatus $status = ListMemberStatus::SUBSCRIBED,
        private ?string $source = null,
        private array $params = [],
        private ?array $data = null,
        private ?\DateTime $subscribedAt = null,
        private ?\DateTime $unsubscribedAt = null,
    ) { }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
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

    public function getStatus(): ListMemberStatus
    {
        return $this->status;
    }

    public function getSource(): ?string
    {
        return $this->source;
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

    /**
     * Get data
     * 
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribedAt;
    }

    public function getUnsubscribedAt(): ?\DateTime
    {
        return $this->unsubscribedAt;
    }
}
