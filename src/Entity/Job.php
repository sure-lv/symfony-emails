<?php

namespace SureLv\Emails\Entity;

use SureLv\Emails\Dto\JobSystemParamsDto;
use SureLv\Emails\Enum\JobKind;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Util\DateTimeUtils;

final class Job
{

    private int $id = 0;

    private string $name = '';

    private JobKind $kind = JobKind::TRANSACTIONAL;

    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    private JobStatus $status = JobStatus::QUEUED;

    private ?string $status_msg = null;

    /**
     * @var array<string, mixed>
     */
    private array $execution_meta = [];

    private ?\DateTime $run_at = null;

    private int $priority = 0;

    private int $attempts = 0;

    private ?string $last_error = null;

    private ?string $dedupe_key = null;

    private ?string $flow_key = null;

    private ?string $flow_instance_id = null;

    private ?int $step_order = null;

    private ?\DateTime $locked_at = null;

    private ?string $locked_by = null;

    private ?\DateTime $cancelled_at = null;

    private ?string $cancel_reason = null;

    private ?int $src_id = null;

    private ?\DateTime $created_at = null;

    private ?\DateTime $updated_at = null;

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     * @return self
     */
    public static function createFromArray(array $data): self
    {
        $job = new self();
        $job->fromArray($data);
        return $job;
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

    public function setKind(JobKind $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    public function getKind(): JobKind
    {
        return $this->kind;
    }

    /**
     * Set params
     * 
     * @param array<string, mixed>|string $params
     * @return self
     */
    public function setParams($params): self
    {
        if (is_string($params)) {
            $params = json_decode($params, true);
        }
        if (!is_array($params)) {
            $params = [];
        }
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

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setStatus(JobStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): JobStatus
    {
        return $this->status;
    }

    public function setStatusMsg(?string $status_msg): self
    {
        $this->status_msg = $status_msg;
        return $this;
    }

    public function getStatusMsg(): ?string
    {
        return $this->status_msg;
    }

    /**
     * Set execution meta
     * 
     * @param array<string, mixed> $execution_meta
     * @return self
     */
    public function setExecutionMeta(array $execution_meta): self
    {
        $this->execution_meta = $execution_meta;
        return $this;
    }

    /**
     * Get execution meta
     * 
     * @return array<string, mixed>
     */
    public function getExecutionMeta(): array
    {
        return $this->execution_meta;
    }

    public function setRunAt(?\DateTime $run_at): self
    {
        $this->run_at = $run_at;
        return $this;
    }

    public function getRunAt(): ?\DateTime
    {
        return $this->run_at;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setLastError(?string $last_error): self
    {
        $this->last_error = $last_error;
        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->last_error;
    }

    public function setDedupeKey(?string $dedupe_key): self
    {
        $this->dedupe_key = $dedupe_key;
        return $this;
    }

    public function getDedupeKey(): ?string
    {
        return $this->dedupe_key;
    }

    public function setFlowKey(?string $flow_key): self
    {
        $this->flow_key = $flow_key;
        return $this;
    }

    public function getFlowKey(): ?string
    {
        return $this->flow_key;
    }

    public function setFlowInstanceId(?string $flow_instance_id): self
    {
        $this->flow_instance_id = $flow_instance_id;
        return $this;
    }

    public function getFlowInstanceId(): ?string
    {
        return $this->flow_instance_id;
    }

    public function setStepOrder(?int $step_order): self
    {
        $this->step_order = $step_order;
        return $this;
    }

    public function getStepOrder(): ?int
    {
        return $this->step_order;
    }

    public function setLockedAt(?\DateTime $locked_at): self
    {
        $this->locked_at = $locked_at;
        return $this;
    }

    public function getLockedAt(): ?\DateTime
    {
        return $this->locked_at;
    }

    public function setLockedBy(?string $locked_by): self
    {
        $this->locked_by = $locked_by;
        return $this;
    }

    public function getLockedBy(): ?string
    {
        return $this->locked_by;
    }

    public function setCancelledAt(?\DateTime $cancelled_at): self
    {
        $this->cancelled_at = $cancelled_at;
        return $this;
    }

    public function getCancelledAt(): ?\DateTime
    {
        return $this->cancelled_at;
    }

    public function setCancelReason(?string $cancel_reason): self
    {
        $this->cancel_reason = $cancel_reason;
        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancel_reason;
    }

    public function setSrcId(?int $src_id): self
    {
        $this->src_id = $src_id;
        return $this;
    }

    public function getSrcId(): ?int
    {
        return $this->src_id;
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

    public function setUpdatedAt(?\DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
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
                case $prfx . 'kind':
                    $this->setKind(JobKind::tryFromString($v) ?? JobKind::TRANSACTIONAL);
                    break;
                case $prfx . 'params':
                    $this->setParams($v);
                    break;
                case $prfx . 'status':
                    $this->setStatus(JobStatus::tryFromString($v) ?? JobStatus::QUEUED);
                    break;
                case $prfx . 'status_msg':
                    $this->setStatusMsg($v);
                    break;
                case $prfx . 'execution_meta':
                    if (is_string($v)) {
                        $v = json_decode($v, true);
                    }
                    if (!is_array($v)) {
                        $v = [];
                    }
                    $this->setExecutionMeta($v);
                    break;
                case $prfx . 'run_at':
                    $this->setRunAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'priority':
                    $this->setPriority($v);
                    break;
                case $prfx . 'attempts':
                    $this->setAttempts($v);
                    break;
                case $prfx . 'last_error':
                    $this->setLastError($v);
                    break;
                case $prfx . 'dedupe_key':
                    $this->setDedupeKey($v);
                    break;
                case $prfx . 'flow_key':
                    $this->setFlowKey($v);
                    break;
                case $prfx . 'flow_instance_id':
                    $this->setFlowInstanceId($v);
                    break;
                case $prfx . 'step_order':
                    $this->setStepOrder($v);
                    break;
                case $prfx . 'locked_at':
                    $this->setLockedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'locked_by':
                    $this->setLockedBy($v);
                    break;
                case $prfx . 'cancelled_at':
                    $this->setCancelledAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'cancel_reason':
                    $this->setCancelReason($v);
                    break;
                case $prfx . 'src_id':
                    $this->setSrcId($v);
                    break;
                case $prfx . 'created_at':
                    $this->setCreatedAt(DateTimeUtils::toDateTime($v));
                    break;
                case $prfx . 'updated_at':
                    $this->setUpdatedAt(DateTimeUtils::toDateTime($v));
                    break;
            }
        }
        return $this;
    }

    /**
     * Get system params
     * 
     * @return JobSystemParamsDto
     */
    public function getSystemParams(): JobSystemParamsDto
    {
        return JobSystemParamsDto::createFromArray($this->params['__'] ?? []);
    }

    /**
     * Set system params
     * 
     * @param JobSystemParamsDto $system_params
     * @return self
     */
    public function setSystemParams(JobSystemParamsDto $system_params): self
    {
        unset($this->params['__']);
        if ($system_params->isEmpty()) {
            return $this;
        }
        $this->params = array_merge(['__' => $system_params->toArray()], $this->params);
        return $this;
    }

    /**
     * Get params without system params
     * 
     * @return array<string, mixed>
     */
    public function getParamsWithoutSystemParams(): array
    {
        $params = $this->params;
        unset($params['__']);
        return $params;
    }

}

