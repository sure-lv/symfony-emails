<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Enum\JobKind;

final class Recipe
{

    private string $name;

    private JobKind $type;

    private string $flow_key = '';

    /**
     * @var array<string>
     */
    private array $stable_keys = [];

    private string $dedupe_template = '';

    /**
     * @var array<string>
     */
    private array $dedupe_params = [];

    private int $default_delay_seconds = 0;

    private int $default_priority = 10;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $contact = new self($data['name'] ?? '', JobKind::tryFromString($data['type'] ?? '') ?? JobKind::TRANSACTIONAL);
        $contact->fromArray($data);
        return $contact;
    }

    public function __construct(string $name, JobKind $type)
    {
        $this->name = $name;
        $this->type = $type;
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

    public function setType(JobKind $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): JobKind
    {
        return $this->type;
    }

    public function setFlowKey(string $flow_key): self
    {
        $this->flow_key = $flow_key;
        return $this;
    }

    public function getFlowKey(): string
    {
        return $this->flow_key;
    }

    /**
     * Set stable keys
     * 
     * @param array<string> $stable_keys
     * @return self
     */
    public function setStableKeys(array $stable_keys): self
    {
        $this->stable_keys = $stable_keys;
        return $this;
    }

    /**
     * Get stable keys
     * 
     * @return array<string>
     */
    public function getStableKeys(): array
    {
        return $this->stable_keys;
    }

    public function setDedupeTemplate(string $dedupe_template): self
    {
        $this->dedupe_template = $dedupe_template;
        return $this;
    }

    public function getDedupeTemplate(): string
    {
        return $this->dedupe_template;
    }

    /**
     * Set dedupe params
     * 
     * @param array<string> $dedupe_params
     * @return self
     */
    public function setDedupeParams(array $dedupe_params): self
    {
        $this->dedupe_params = $dedupe_params;
        return $this;
    }

    /**
     * Get dedupe params
     * 
     * @return array<string>
     */
    public function getDedupeParams(): array
    {
        return $this->dedupe_params;
    }

    public function setDefaultDelaySeconds(int $default_delay_seconds): self
    {
        $this->default_delay_seconds = $default_delay_seconds;
        return $this;
    }

    public function getDefaultDelaySeconds(): int
    {
        return $this->default_delay_seconds;
    }

    public function setDefaultPriority(int $default_priority): self
    {
        $this->default_priority = $default_priority;
        return $this;
    }

    public function getDefaultPriority(): int
    {
        return $this->default_priority;
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
                case $prfx . 'name':
                    $this->setName($v);
                    break;
                case $prfx . 'type':
                    $this->setType(JobKind::tryFromString($v) ?? JobKind::TRANSACTIONAL);
                    break;
                case $prfx . 'flow_key':
                    $this->setFlowKey($v);
                    break;
                case $prfx . 'stable_keys':
                    $this->setStableKeys($v);
                    break;
                case $prfx . 'dedupe_template':
                    $this->setDedupeTemplate($v);
                    break;
                case $prfx . 'dedupe_params':
                    $this->setDedupeParams($v);
                    break;
                case $prfx . 'default_delay_seconds':
                    $this->setDefaultDelaySeconds($v);
                    break;
                case $prfx . 'default_priority':
                    $this->setDefaultPriority($v);
                    break;
            }
        }
        return $this;
    }

    public function isList(): bool
    {
        return $this->type == JobKind::LIST;
    }

    public function isTransactional(): bool
    {
        return $this->type == JobKind::TRANSACTIONAL;
    }

}