<?php

namespace SureLv\Emails\Dto;

final class EmailMessageParamsDto
{

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    private ?EmailMessageDto $emailMessageDto = null;

    /**
     * Constructor
     * 
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
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
     * Set data
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     * 
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setDataItem(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getDataItem(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function setEmailMessageDto(EmailMessageDto $emailMessageDto): self
    {
        $this->emailMessageDto = $emailMessageDto;
        return $this;
    }
    
    public function getEmailMessageDto(): ?EmailMessageDto
    {
        return $this->emailMessageDto;
    }
    
}