<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Util\DateTimeUtils;

final class TypeUnsubscribe
{

    private int $id = 0;

    private int $contact_id = 0;

    private string $scope_type = '';

    private int $scope_id = 0;

    private string $email_type = '';

    private \DateTime $created_at;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $event = new self();
        $event->fromArray($data);
        return $event;
    }

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setScopeId(int $scope_id): self
    {
        $this->scope_id = $scope_id;
        return $this;
    }

    public function getScopeId(): int
    {
        return $this->scope_id;
    }

    public function setEmailType(string $email_type): self
    {
        $this->email_type = $email_type;
        return $this;
    }

    public function getEmailType(): string
    {
        return $this->email_type;
    }

    public function setCreatedAt(\DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function prePersist(): self
    {
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
                case $prfx . 'contact_id':
                    $this->setContactId($v);
                    break;
                case $prfx . 'scope_type':
                    $this->setScopeType($v);
                    break;
                case $prfx . 'scope_id':
                    $this->setScopeId($v);
                    break;
                case $prfx . 'email_type':
                    $this->setEmailType($v);
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v) ?? new \DateTime());
                    break;
            }
        }
        return $this;
    }

}

