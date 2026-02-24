<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\Job;
use SureLv\Emails\Enum\JobKind;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Util\DateTimeUtils;

class JobModel extends AbstractModel
{
    
    const LOCK_TIMEOUT = '-5 minutes';

    /**
     * Add job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return bool
     */
    public function add(Job $job): bool
    {
        $job->prePersist();

        // Ensure required fields are set
        if ($job->getRunAt() === null) {
            $job->setRunAt(new \DateTime());
        }

        $data = [
            'name' => $job->getName(),
            'kind' => $job->getKind()->value,
            'params' => json_encode($job->getParams()),
            'status' => $job->getStatus()->value,
            'status_msg' => $job->getStatusMsg(),
            'execution_meta' => json_encode($job->getExecutionMeta()),
            'run_at' => DateTimeUtils::toDbDateTime($job->getRunAt()),
            'priority' => $job->getPriority(),
            'attempts' => $job->getAttempts(),
            'last_error' => $job->getLastError(),
            'dedupe_key' => $job->getDedupeKey(),
            'flow_key' => $job->getFlowKey(),
            'flow_instance_id' => $job->getFlowInstanceId(),
            'step_order' => $job->getStepOrder(),
            'locked_at' => DateTimeUtils::toDbDateTime($job->getLockedAt()),
            'locked_by' => $job->getLockedBy(),
            'cancelled_at' => DateTimeUtils::toDbDateTime($job->getCancelledAt()),
            'cancel_reason' => $job->getCancelReason(),
            'src_id' => $job->getSrcId(),
            'created_at' => DateTimeUtils::toDbDateTime($job->getCreatedAt()),
            'updated_at' => DateTimeUtils::toDbDateTime($job->getUpdatedAt() ?? $job->getCreatedAt()),
        ];

        // Build SQL and parameters for prepared statement to bypass schema introspection
        $columns = [];
        $placeholders = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $columns[] = '`' . $key . '`'; // Escape column names
                $placeholders[] = ':' . $key;
                $params[$key] = $value;
            }
        }
        
        if (empty($columns)) {
            return false;
        }
        
        $sql = 'INSERT INTO `' . $this->tablePrefix . 'jobs` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        
        try {
            $stmt = $this->connection->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->executeStatement();
            
            $id = intval($this->connection->lastInsertId());
            if ($id <= 0) {
                return false;
            }
            $job->setId($id);
            return true;
        } catch (\Exception $e) {
            // Handle duplicate dedupe_key or other errors
            return false;
        }
    }

    /**
     * Get job by dedupe key
     * 
     * @param string $dedupeKey
     * @return \SureLv\Emails\Entity\Job|null
     */
    public function getByDedupeKey(string $dedupeKey): ?Job
    {
        $sql = 'SELECT * FROM `' . $this->tablePrefix . 'jobs` WHERE `dedupe_key` = :dedupe_key LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('dedupe_key', $dedupeKey);
        $dbRes = $stmt->executeQuery();
        // Use fetchNumeric to avoid type introspection issues
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return Job::createFromArray($dbRow);
    }

    /**
     * Claim due transactional jobs
     * 
     * @param int $limit
     * @param string $worker
     * @param bool $lockJobs
     * @return \SureLv\Emails\Entity\Job[]
     */
    public function claimDueTransactionalJobs(int $limit, string $worker, bool $lockJobs = true): array
    {
        return $this->claimDueJobs(JobKind::TRANSACTIONAL, $limit, $worker, $lockJobs);
    }

    /**
     * Claim due list jobs
     * 
     * @param int $limit
     * @param string $worker
     * @param bool $lockJobs
     * @return \SureLv\Emails\Entity\Job[]
     */
    public function claimDueListJobs(int $limit, string $worker, bool $lockJobs = true): array
    {
        return $this->claimDueJobs(JobKind::LIST, $limit, $worker, $lockJobs);
    }

    /**
     * Claim due jobs
     * 
     * @param JobKind|null $kind
     * @param int $limit
     * @param string $worker
     * @param bool $lockJobs
     * @return \SureLv\Emails\Entity\Job[]
     */
    public function claimDueJobs(?JobKind $kind, int $limit, string $worker, bool $lockJobs = true): array
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $nowStr = DateTimeUtils::toDbDateTime($now);
        
        // Lock timeout: 5 minutes
        $lockTimeout = (new \DateTime())->modify(self::LOCK_TIMEOUT);
        $lockTimeoutStr = DateTimeUtils::toDbDateTime($lockTimeout);

        $sqlWhere = '';
        if ($kind) {
            $sqlWhere = 'kind = :kind AND ';
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'jobs 
                WHERE ' . $sqlWhere . 'status = :status 
                AND run_at <= :now 
                AND (cancelled_at IS NULL)
                AND (locked_at IS NULL OR locked_at < :lock_timeout)
                ORDER BY priority DESC, run_at ASC, id ASC
                LIMIT ' . (int)$limit;
        
        $stmt = $this->connection->prepare($sql);
        if ($kind) {
            $stmt->bindValue('kind', $kind->value);
        }
        $stmt->bindValue('status', JobStatus::QUEUED->value);
        $stmt->bindValue('now', $nowStr);
        $stmt->bindValue('lock_timeout', $lockTimeoutStr);
        
        $dbRes = $stmt->executeQuery();
        $rows = $dbRes->fetchAllAssociative();
        
        $jobs = [];
        foreach ($rows as $row) {
            $job = Job::createFromArray($row);
            $jobs[] = $job;
            if ($lockJobs) {
                // Try to claim the job atomically
                $claimed = $this->claimJob($job->getId(), $worker);
                if ($claimed) {
                    $job->setStatus(JobStatus::RUNNING);
                    $job->setLockedAt($now);
                    $job->setLockedBy($worker);
                    $job->setAttempts($job->getAttempts() + 1);
                }
            }
        }
        
        return $jobs;
    }

    /**
     * Claim a job atomically
     * 
     * @param int $jobId
     * @param string $worker
     * @return bool
     */
    public function claimJob(int $jobId, string $worker): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $nowStr = DateTimeUtils::toDbDateTime($now);
        $lockTimeout = new \DateTime('now', new \DateTimeZone('UTC'));
        $lockTimeout->modify(self::LOCK_TIMEOUT);
        $lockTimeoutStr = DateTimeUtils::toDbDateTime($lockTimeout);

        $sql = 'UPDATE ' . $this->tablePrefix . 'jobs 
                SET status = :status, 
                    locked_at = :locked_at, 
                    locked_by = :locked_by,
                    attempts = attempts + 1,
                    updated_at = :updated_at
                WHERE id = :id 
                AND status = :old_status 
                AND (locked_at IS NULL OR locked_at < :lock_timeout)
                AND (cancelled_at IS NULL)';
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $jobId);
        $stmt->bindValue('status', JobStatus::RUNNING->value);
        $stmt->bindValue('old_status', JobStatus::QUEUED->value);
        $stmt->bindValue('locked_at', $nowStr);
        $stmt->bindValue('locked_by', $worker);
        $stmt->bindValue('lock_timeout', $lockTimeoutStr);
        $stmt->bindValue('updated_at', $nowStr);
        
        $rowsAffected = $stmt->executeStatement();
        return $rowsAffected > 0;
    }

    /**
     * Fail a job
     * 
     * @param int $jobId
     * @param string $errorMessage
     * @param array<string, mixed> $executionMeta
     * @return bool
     */
    public function failJob(int $jobId, string $errorMessage, array $executionMeta = []): bool
    {
        $sql = '
            UPDATE ' . $this->tablePrefix . 'jobs 
            SET status = :status, 
                status_msg = :status_msg, 
                execution_meta = :execution_meta,
                updated_at = :updated_at
            WHERE id = :id
            ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('status', JobStatus::FAILED->value);
        $stmt->bindValue('status_msg', $errorMessage);
        $stmt->bindValue('execution_meta', json_encode($executionMeta));
        $stmt->bindValue('updated_at', DateTimeUtils::toDbDateTime(new \DateTime()));
        $stmt->bindValue('id', $jobId);
        $rowsAffected = $stmt->executeStatement();
        return $rowsAffected > 0;
    }

    /**
     * Update job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return bool
     */
    public function update(Job $job): bool
    {
        $job->setUpdatedAt(new \DateTime());
        
        $data = [
            'name' => $job->getName(),
            'kind' => $job->getKind()->value,
            'params' => json_encode($job->getParams()),
            'status' => $job->getStatus()->value,
            'status_msg' => $job->getStatusMsg(),
            'execution_meta' => json_encode($job->getExecutionMeta()),
            'run_at' => DateTimeUtils::toDbDateTime($job->getRunAt()),
            'priority' => $job->getPriority(),
            'attempts' => $job->getAttempts(),
            'last_error' => $job->getLastError(),
            'dedupe_key' => $job->getDedupeKey(),
            'flow_key' => $job->getFlowKey(),
            'flow_instance_id' => $job->getFlowInstanceId(),
            'step_order' => $job->getStepOrder(),
            'locked_at' => DateTimeUtils::toDbDateTime($job->getLockedAt()),
            'locked_by' => $job->getLockedBy(),
            'cancelled_at' => DateTimeUtils::toDbDateTime($job->getCancelledAt()),
            'cancel_reason' => $job->getCancelReason(),
            'src_id' => $job->getSrcId(),
            'updated_at' => DateTimeUtils::toDbDateTime($job->getUpdatedAt()),
        ];

        // Remove null values for optional fields
        $data = array_filter($data, function($value) {
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        // Build UPDATE SQL with prepared statement to bypass schema introspection
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = '`' . $key . '` = :' . $key;
            $params[$key] = $value;
        }
        
        $sql = 'UPDATE `' . $this->tablePrefix . 'jobs` SET ' . implode(', ', $setParts) . ' WHERE `id` = :id';
        $params['id'] = $job->getId();

        try {
            $stmt = $this->connection->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->executeStatement();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update job status
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @param JobStatus $status
     * @param ?string $statusMsg
     * @param array<string, mixed> $executionMeta
     * @return bool
     */
    public function updateStatus(Job $job, JobStatus $status, ?string $statusMsg = null, array $executionMeta = []): bool
    {
        $job->setStatus($status);
        $dataToUpdate = [
            'status' => $job->getStatus()->value,
        ];
        if ($statusMsg) {
            $job->setStatusMsg($statusMsg);
            $dataToUpdate['status_msg'] = $statusMsg;
        }
        if (count($executionMeta) > 0) {
            $job->setExecutionMeta($executionMeta);
            $dataToUpdate['execution_meta'] = json_encode($executionMeta);
        }
        $this->connection->update($this->tablePrefix . 'jobs', $dataToUpdate, [
            'id' => $job->getId(),
        ]);
        return true;
    }

    /**
     * Get job by ID
     * 
     * @param int $id
     * @return \SureLv\Emails\Entity\Job|null
     */
    public function getById(int $id): ?Job
    {
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'jobs WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return Job::createFromArray($dbRow);
    }

    /**
     * Get draft jobs
     * 
     * @return \SureLv\Emails\Entity\Job[]
     */
    public function getDraftJobs(): array
    {
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'jobs WHERE status = :status LIMIT 100';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('status', JobStatus::DRAFT->value);
        $dbRes = $stmt->executeQuery();
        $dbRows = $dbRes->fetchAllAssociative();
        $jobs = [];
        foreach ($dbRows as $dbRow) {
            $jobs[] = Job::createFromArray($dbRow);
        }
        return $jobs;
    }

}