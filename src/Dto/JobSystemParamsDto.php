<?php

namespace SureLv\Emails\Dto;

use JsonSerializable;

final class JobSystemParamsDto implements JsonSerializable
{

    /**
     * @var array<JobSystemParamsListDto>
     */
    private array $lists = [];

    private ?int $contactId = null;

    private ?string $contactEmail = null;

    private ?string $scopeType = null;

    private ?int $scopeId = null;

    private bool $skip_next_message = false;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $params = new self();
        $params->fromArray($data);
        return $params;
    }

    public function __construct()
    {
    }

    /**
     * Set lists
     * 
     * @param array<JobSystemParamsListDto|array<string, mixed>> $lists
     * @return self
     */
    public function setLists(array $lists): self
    {
        $this->lists = [];
        foreach ($lists as $list) {
            if (is_array($list)) {
                $list = JobSystemParamsListDto::createFromArray($list);
            }
            if (!$list instanceof JobSystemParamsListDto) {
                continue;
            }
            $this->addList($list);
        }
        return $this;
    }

    /**
     * Get lists
     * 
     * @return array<JobSystemParamsListDto>
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    public function addList(JobSystemParamsListDto $list): self
    {
        foreach ($this->lists as $existingList) {
            if ($existingList->isEqualToList($list)) {
                return $this;
            }
        }
        $this->lists[] = $list;
        return $this;
    }

    public function getList(int $index = 0): ?JobSystemParamsListDto
    {
        return $this->lists[$index] ?? null;
    }

    public function getListById(int $id): ?JobSystemParamsListDto
    {
        foreach ($this->lists as $list) {
            if ($list->getId() === $id) {
                return $list;
            }
        }
        return null;
    }

    public function setContactId(?int $contactId): self
    {
        $this->contactId = $contactId;
        return $this;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setScopeType(?string $scopeType): self
    {
        $this->scopeType = $scopeType;
        return $this;
    }

    public function getScopeType(): ?string
    {
        return $this->scopeType;
    }

    public function setScopeId(?int $scopeId): self
    {
        $this->scopeId = $scopeId;
        return $this;
    }

    public function getScopeId(): ?int
    {
        return $this->scopeId;
    }

    public function setSkipNextMessage(bool $skip_next_message): self
    {
        $this->skip_next_message = $skip_next_message;
        return $this;
    }

    public function getSkipNextMessage(): bool
    {
        return $this->skip_next_message;
    }

    /**
     * Set data from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public function fromArray(array $data): self
    {
        foreach ($data as $k => $v) {
            switch ($k) {
                case 'lists':
                    if (is_string($v)) {
                        $v = json_decode($v, true);
                    }
                    if (!is_array($v)) {
                        $v = [];
                    }
                    $this->setLists($v);
                    break;
                case 'contact_id':
                    $this->setContactId($v);
                    break;
                case 'contact_email':
                    $this->setContactEmail($v);
                    break;
                case 'scope_type':
                    $this->setScopeType($v);
                    break;
                case 'scope_id':
                    $this->setScopeId($v);
                    break;
                case 'skip_next':
                    $this->setSkipNextMessage(boolval($v));
                    break;
            }
        }
        return $this;
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $res = [];
        if (count($this->lists) > 0) {
            $res['lists'] = [];
            foreach ($this->lists as $list) {
                $res['lists'][] = $list->toArray();
            }
        }
        if ($this->contactId) {
            $res['contact_id'] = $this->contactId;
        }
        if ($this->contactEmail) {
            $res['contact_email'] = $this->contactEmail;
        }
        if ($this->scopeType) {
            $res['scope_type'] = $this->scopeType;
            if (!is_null($this->scopeId)) {
                $res['scope_id'] = $this->scopeId;
            }
        }
        if ($this->skip_next_message) {
            $res['skip_next'] = $this->skip_next_message;
        }
        return $res;
    }

    /**
     * Convert to JSON
     * 
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Check if empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->lists) <= 0 && is_null($this->contactId) && is_null($this->contactEmail) && is_null($this->scopeType) && is_null($this->scopeId);
    }

}