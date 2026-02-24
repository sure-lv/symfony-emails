<?php

namespace SureLv\Emails\Message;

class EnqueueTransactionalEmailMessage
{

    /**
     * Constructor
     * 
     * @param string $recipeName
     * @param int $contactId
     * @param ?\DateTimeImmutable $runAt
     * @param array<string, mixed> $params
     * @param ?string $dedupeKey
     * @param ?string $flowKey
     * @param ?string $flowInstanceId
     * @param ?int $stepOrder
     * @param ?int $priority
     * @param ?int $srcId
     */
	public function __construct(private string $recipeName, private int $contactId, private ?\DateTimeImmutable $runAt = null, private array $params = [], private ?string $dedupeKey = null, private ?string $flowKey = null, private ?string $flowInstanceId = null, private ?int $stepOrder = null, private ?int $priority = null, private ?int $srcId = null)
    {
    }

    public function getRecipeName(): string
    {
        return $this->recipeName;
    }

    public function getContactId(): int
    {
        return $this->contactId;
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

    public function getFlowInstanceId(): ?string
    {
        return $this->flowInstanceId;
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

}
