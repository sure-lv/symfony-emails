<?php

namespace SureLv\Emails\Message;

class EnqueueListEmailMessage
{

    /**
     * Constructor
     * 
     * @param string $recipeName
     * @param int $listId
     * @param string|null $listSubType
     * @param ?\DateTimeImmutable $runAt
     * @param array<string, mixed> $params
     * @param ?string $dedupeKey
     * @param ?string $flowKey
     * @param ?int $stepOrder
     * @param ?int $priority
     * @param ?int $srcId
     * @param bool $isDraft
     */
    public function __construct(private string $recipeName, private int $listId = 0, private ?string $listSubType = null, private ?\DateTimeImmutable $runAt = null, private array $params = [], private ?string $dedupeKey = null, private ?string $flowKey = null, private ?int $stepOrder = null, private ?int $priority = null, private ?int $srcId = null, private bool $isDraft = false)
    {
    }

    public function getRecipeName(): string
    {
        return $this->recipeName;
    }

    public function getListId(): int
    {
        return $this->listId;
    }

    public function getListSubType(): ?string
    {
        return $this->listSubType;
    }

    public function getRunAt(): ?\DateTimeImmutable
    {
        return $this->runAt;
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

    public function getDedupeKey(): ?string
    {
        return $this->dedupeKey;
    }

    public function getFlowKey(): ?string
    {
        return $this->flowKey;
    }

    public function getStepOrder(): ?int
    {
        return $this->stepOrder;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getSrcId(): ?int
    {
        return $this->srcId;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

}