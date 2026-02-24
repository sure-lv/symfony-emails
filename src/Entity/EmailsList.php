<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Util\DateTimeUtils;

final class EmailsList
{

    private int $id = 0;

    private string $name = '';

    private string $title = '';

    private ?string $scope_type = null;

    private bool $supports_unsubscribe = true;

    private string $provider_class = '';

    private ?\DateTime $created_at = null;

    /**
     * @var EmailsListMember[]
     */
    private array $members = [];

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $list = new self();
        $list->fromArray($data);
        return $list;
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

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setScopeType(?string $scope_type): self
    {
        $this->scope_type = $scope_type;
        return $this;
    }

    public function getScopeType(): ?string
    {
        return $this->scope_type;
    }

    public function setSupportsUnsubscribe(bool $supports_unsubscribe): self
    {
        $this->supports_unsubscribe = $supports_unsubscribe;
        return $this;
    }

    public function getSupportsUnsubscribe(): bool
    {
        return $this->supports_unsubscribe;
    }

    public function setProviderClass(string $provider_class): self
    {
        $this->provider_class = $provider_class;
        return $this;
    }

    public function getProviderClass(): string
    {
        return $this->provider_class;
    }

    public function setCreatedAt(?\DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    /**
     * Set members
     * 
     * @param EmailsListMember[] $members
     * @return self
     */
    public function setMembers(array $members): self
    {
        $this->members = [];
        foreach ($members as $member) {
            if (!$member instanceof EmailsListMember) {
                continue;
            }
            $this->addMember($member);
        }
        return $this;
    }

    /**
     * Get members
     * 
     * @return EmailsListMember[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    public function addMember(EmailsListMember $member): self
    {
        $this->members[] = $member;
        return $this;
    }

    public function prePersist(): self
    {
        if (!$this->created_at instanceof \DateTime) {
            $this->created_at = new \DateTime();
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
                case $prfx . 'name':
                    $this->setName($v);
                    break;
                case $prfx . 'title':
                    $this->setTitle($v);
                    break;
                case $prfx . 'scope_type':
                    $this->setScopeType($v);
                    break;
                case $prfx . 'supports_unsubscribe':
                    $this->setSupportsUnsubscribe((bool)$v);
                    break;
                case $prfx . 'provider_class':
                    $this->setProviderClass($v);
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v));
                    break;
            }
        }
        return $this;
    }

}