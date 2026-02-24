<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Util\DateTimeUtils;

final class EmailsListMember
{

    private int $id = 0;

    private int $list_id = 0;

    private int $contact_id = 0;

    private string $scope_type = '';

    private ?int $scope_id = null;

    private ListMemberStatus $status = ListMemberStatus::SUBSCRIBED;

    private ?string $source = null;

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $data = null;

    private ?\DateTime $subscribed_at = null;

    private ?\DateTime $unsubscribed_at = null;

    private ?EmailsList $list = null;

    private ?Contact $contact = null;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $contact = new self();
        $contact->fromArray($data);
        return $contact;
    }

    public function __construct() {}

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setListId(int $list_id): self
    {
        $this->list_id = $list_id;
        return $this;
    }

    public function getListId(): int
    {
        return $this->list_id;
    }

    public function setContactId(int $contact_id): self
    {
        $this->contact_id = $contact_id;
        return $this;
    }

    public function getContactId(): int
    {
        return $this->contact_id;
    }

    public function setScopeType(string $scope_type): self
    {
        $this->scope_type = $scope_type;
        return $this;
    }

    public function getScopeType(): string
    {
        return $this->scope_type;
    }

    public function setScopeId(?int $scope_id): self
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    public function getScopeId(): ?int
    {
        return $this->scope_id;
    }

    public function setStatus(ListMemberStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): ListMemberStatus
    {
        return $this->status;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Set params
     * 
     * @param array<string, mixed> $params
     * @return self
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
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

    public function setParam(string $key, mixed $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function getParam(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Set data
     * 
     * @param array<string, mixed>|string|null $data
     * @return self
     */
    public function setData($data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            $data = null;
        }
        $this->data = $data;
        return $this;
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

    public function setDataItem(string $key, mixed $value): self
    {
        if (!is_array($this->data)) {
            $this->data = [];
        }
        $this->data[$key] = $value;
        return $this;
    }

    public function getDataItem(string $key): mixed
    {
        if (!is_array($this->data)) {
            return null;
        }
        return $this->data[$key] ?? null;
    }

    public function setSubscribedAt(?\DateTime $subscribed_at): self
    {
        $this->subscribed_at = $subscribed_at;
        return $this;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribed_at;
    }

    public function setUnsubscribedAt(?\DateTime $unsubscribed_at): self
    {
        $this->unsubscribed_at = $unsubscribed_at;
        return $this;
    }

    public function getUnsubscribedAt(): ?\DateTime
    {
        return $this->unsubscribed_at;
    }

    public function setList(?EmailsList $list): self
    {
        $this->list = $list;
        return $this;
    }

    public function getList(): ?EmailsList
    {
        return $this->list;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function prePersist(): self
    {
        if (!$this->subscribed_at instanceof \DateTime) {
            $this->subscribed_at = new \DateTime();
        }
        return $this;
    }

    /**
	 * Set data from array
	 * 
	 * @param array<string, mixed> $data
	 * @param string $prfx
	 * @return self
	 */
	public function fromArray(array $data, string $prfx = ''): self
	{
        foreach ($data as $k => $v) {
            switch ($k) {
                case $prfx . 'id':
                    $this->setId($v);
                    break;
                case $prfx . 'list_id':
                    $this->setListId($v);
                    break;
                case $prfx . 'contact_id':
                    $this->setContactId($v);
                    break;
                case $prfx . 'scope_type':
                    $this->setScopeType($v);
                    break;
                case $prfx . 'scope_id':
                    $this->setScopeId($v);
                    break;
                case $prfx . 'status':
                    $this->setStatus(ListMemberStatus::tryFromString($v) ?? ListMemberStatus::SUBSCRIBED);
                    break;
                case $prfx . 'source':
                    $this->setSource($v);
                    break;
                case $prfx . 'params':
                    if (is_string($v)) {
                        $v = json_decode($v, true);
                    }
                    if (!is_array($v)) {
                        $v = [];
                    }
                    $this->setParams($v);
                    break;
                case $prfx . 'data':
                    $this->setData($v);
                    break;
                case $prfx . 'subscribed_at':
                    $this->setSubscribedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'unsubscribed_at':
                    $this->setUnsubscribedAt(DateTimeUtils::toDateTime($v));
                    break;
            }
        }
        return $this;
    }

}