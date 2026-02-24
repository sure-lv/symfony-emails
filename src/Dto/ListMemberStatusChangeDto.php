<?php

namespace SureLv\Emails\Dto;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Enum\ListMemberStatus;

final class ListMemberStatusChangeDto
{

    private ?EmailsListMember $listMember = null;

    private ?Contact $contact = null;

    private ?EmailsList $list = null;

    /**
     * Constructor
     * 
     * @param int $listMemberId
     * @param ?string $subType
     * @param ListMemberStatus $toStatus
     * @param ?ListMemberStatus $fromStatus
     * @param array<string, mixed> $params
     * @param int $contactId
     * @param int $listId
     * @param ?string $scopeType
     * @param int $scopeId
     * @param ?\DateTime $occurredAt
     */
    public function __construct(private int $listMemberId, private ?string $subType, private ListMemberStatus $toStatus, private ?ListMemberStatus $fromStatus = null, private array $params = [], private int $contactId = 0, private int $listId = 0, private ?string $scopeType = null, private int $scopeId = 0, private ?\DateTime $occurredAt = null)
    {
    }

    public function setListMember(EmailsListMember $listMember): self
    {
        $this->listMember = $listMember;
        return $this;
    }

    public function getListMember(): ?EmailsListMember
    {
        return $this->listMember;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setList(EmailsList $list): self
    {
        $this->list = $list;
        return $this;
    }

    public function getList(): ?EmailsList
    {
        return $this->list;
    }

    public function getListMemberId(): int
    {
        return $this->listMemberId;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }

    public function getToStatus(): ListMemberStatus
    {
        return $this->toStatus;
    }

    public function getFromStatus(): ?ListMemberStatus
    {
        return $this->fromStatus;
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

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getListId(): int
    {
        return $this->listId;
    }

    public function getScopeType(): ?string
    {
        return $this->scopeType;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    public function getOccurredAt(): ?\DateTime
    {
        return $this->occurredAt;
    }
}