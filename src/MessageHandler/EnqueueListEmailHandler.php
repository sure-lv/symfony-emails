<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Dto\JobSystemParamsDto;
use SureLv\Emails\Dto\JobSystemParamsListDto;
use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Enum\JobKind;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Message\EnqueueListEmailMessage;
use SureLv\Emails\Model\EmailsListModel;
use SureLv\Emails\Model\JobModel;
use SureLv\Emails\Service\DedupeKeyFactory;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Service\FlowIdFactory;
use SureLv\Emails\Service\ModelService;
use SureLv\Emails\Service\RegistryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EnqueueListEmailHandler
{

    public function __construct(
        private ModelService $modelService, 
        private RegistryService $registryService, 
        private DedupeKeyFactory $dedupe, 
        private FlowIdFactory $flowIdFactory,
        private EmailsLogger $emailsLogger
    )
    {
    }

    public function __invoke(EnqueueListEmailMessage $message): void
    {
        $recipeName = $message->getRecipeName();
        
        // Log immediately - this proves the handler is being called
        $this->emailsLogger->logInfo('EnqueueListEmailHandler invoked', [
            'message_class' => get_class($message),
            'handler_class' => self::class,
            'list_id' => $message->getListId(),
            'recipe' => $recipeName,
        ]);

        // Get recipe
        if (!$this->registryService->hasRecipe($recipeName)) {
            $this->emailsLogger->logError('Recipe not found in registry', [
                'recipe' => $recipeName,
            ]);
            return;
        }
        $recipe = $this->registryService->getRecipe($recipeName);

        // Validate recipe
        if (!$recipe->isList()) {
            $this->emailsLogger->logError('Invalid list email recipe', [
                'recipe' => $recipeName,
            ]);
            return;
        }

        // Get list
        $list = null;
        if ($message->getListId() > 0) {
            $list = $this->getListById($message->getListId());
            if (is_null($list)) {
                $this->emailsLogger->logWarning('Failed to retrieve list, skipping job creation', [
                    'recipe' => $recipeName,
                    'list_id' => $message->getListId(),
                ]);
                return;
            }
        }
        $listId = $list ? $list->getId() : 0;

        $this->emailsLogger->logInfo('Processing list email message', [
            'recipe' => $recipeName,
            'list_id' => $listId,
            'params' => $message->getParams(),
        ]);

        // Get message params
        $messageParams = $message->getParams();
        
        // Get system params
        $systemParams = JobSystemParamsDto::createFromArray($messageParams['__'] ?? []);
        unset($messageParams['__']);

        // Add list to system params
        if ($list) {
            $systemParams->addList(new JobSystemParamsListDto($list->getId(), $message->getListSubType()));
        }

        // Get job data
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $delay  = $recipe->getDefaultDelaySeconds();
        $runAt  = $message->getRunAt() ?? $nowUtc->modify(sprintf('+%d seconds', $delay));
        $priority = $message->getPriority() ?? $recipe->getDefaultPriority();
        $dedupeKey = $message->getDedupeKey() ?? $this->dedupe->build($recipe, $messageParams, $systemParams->getContactId(), $message->getStepOrder(), $runAt);
        $flowKey = $message->getFlowKey() ?? $recipe->getFlowKey();
        $flowInstanceId = $this->flowIdFactory->forRecipe($recipe, $messageParams);
        
        // Get job model
        $jobModel = $this->modelService->getModel(JobModel::class); /** @var \SureLv\Emails\Model\JobModel $jobModel */

        // Check if job with same dedupe key already exists
        $existingJob = $jobModel->getByDedupeKey($dedupeKey);
        if ($existingJob instanceof Job) {
            // Job already exists, skip
            $this->emailsLogger->logInfo('Job with same dedupe key already exists, skipping', [
                'recipe' => $recipeName,
                'dedupe_key' => $dedupeKey,
                'existing_job_id' => $existingJob->getId(),
                'list_id' => $listId,
            ]);
            return;
        }

        // Create job
        $job = new Job();
        $job
            ->setName($recipeName)
            ->setKind(JobKind::LIST)
            ->setParams($messageParams)
            ->setStatus($message->isDraft() ? JobStatus::DRAFT : JobStatus::QUEUED)
            ->setRunAt(\DateTime::createFromImmutable($runAt))
            ->setPriority($priority)
            ->setDedupeKey($dedupeKey)
            ->setFlowKey($flowKey)
            ->setFlowInstanceId($flowInstanceId)
            ->setStepOrder($message->getStepOrder())
            ->setSrcId($message->getSrcId())
            ;
        $job->setSystemParams($systemParams);

        try {
            $jobModel->add($job);
            $this->emailsLogger->logInfo('Job created successfully', [
                'recipe' => $recipeName,
                'job_id' => $job->getId(),
                'list_id' => $listId,
                'dedupe_key' => $dedupeKey,
                'run_at' => $runAt->format('Y-m-d H:i:s'),
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            $this->emailsLogger->logError('Failed to create job', [
                'recipe' => $recipeName,
                'list_id' => $listId,
                'dedupe_key' => $dedupeKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Get list by id
     * 
     * @param int $listId
     * @return \SureLv\Emails\Entity\EmailsList|null
     */
    private function getListById(int $listId): ?EmailsList
    {
        $emailsListModel = $this->modelService->getModel(EmailsListModel::class); /** @var \SureLv\Emails\Model\EmailsListModel $emailsListModel */
        return $emailsListModel->getList($listId);
    }

}
