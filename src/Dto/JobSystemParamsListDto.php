<?php

namespace SureLv\Emails\Dto;

use JsonSerializable;

final class JobSystemParamsListDto implements JsonSerializable
{

    /**
     * @var int
     */
    private int $id = 0;

    /**
     * @var ?string
     */
    private ?string $subType = null;

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

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

    /**
     * Constructor
     * 
     * @param int $id
     * @param ?string $subType
     * @param array<string, mixed> $params
     */
    public function __construct(int $id = 0, ?string $subType = null, array $params = [])
    {
        $this->id = $id;
        $this->subType = $subType;
        $this->params = $params;
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

    public function setSubType(?string $subType): self
    {
        $this->subType = $subType;
        return $this;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
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
                case 'id':
                    $this->setId($v);
                    break;
                case 'sub_type':
                    $this->setSubType($v);
                    break;
                case 'params':
                    if (is_string($v)) {
                        $v = json_decode($v, true);
                    }
                    if (!is_array($v)) {
                        $v = [];
                    }
                    $this->setParams($v);
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
        $res = [
            'id' => $this->id,
        ];
        if ($this->subType) {
            $res['sub_type'] = $this->subType;
        }
        if (count($this->params) > 0) {
            $res['params'] = $this->params;
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
     * Check if list is equal to another list
     * 
     * @param JobSystemParamsListDto $list
     * @return bool
     */
    public function isEqualToList(JobSystemParamsListDto $list): bool
    {
        return $this->id === $list->getId() && $this->subType === $list->getSubType() && $this->params == $list->getParams();
    }

}