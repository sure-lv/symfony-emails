<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Dto\JobSystemParamsDto;
use SureLv\Emails\Service\DedupeKeyFactory;
use SureLv\Emails\Service\FlowIdFactory;
use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Enum\JobKind;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Message\EnqueueTransactionalEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\JobModel;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Service\ModelService;
use SureLv\Emails\Service\RegistryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EnqueueTransactionalEmailHandler
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

    public function __invoke(EnqueueTransactionalEmailMessage $message): void
    {
        $recipeName = $message->getRecipeName();
        
        // Log immediately - this proves the handler is being called
        $this->emailsLogger->logInfo('EnqueueTransactionalEmailHandler invoked', [
            'message_class' => get_class($message),
            'handler_class' => self::class,
            'contact_id' => $message->getContactId(),
            'recipe' => $recipeName,
        ]);

        // Get recipe
        if (!$this->registryService->hasRecipe($recipeName)) {
            $this->emailsLogger->logError('Recipe not found in registry', [
                'recipe' => $recipeName,
            ]);
            throw new \Exception('System error. Please contact support.');
        }
        $recipe = $this->registryService->getRecipe($recipeName);

        // Validate recipe
        if (!$recipe->isTransactional()) {
            $this->emailsLogger->logError('Invalid transactional email recipe', [
                'recipe' => $recipeName,
            ]);
            return;
        }

        // Get contact
        $contact = $this->getContactById($message->getContactId());
        if (is_null($contact)) {
            $this->emailsLogger->logWarning('Failed to retrieve contact, skipping job creation', [
                'recipe' => $recipeName,
                'contact_id' => $message->getContactId(),
            ]);
            return;
        }
        
        $this->emailsLogger->logInfo('Processing transactional email message', [
            'recipe' => $recipeName,
            'email' => $contact->getEmail(),
            'params' => $message->getParams(),
        ]);

        // Get message params
        $messageParams = $message->getParams();

        // Get system params
        $systemParams = JobSystemParamsDto::createFromArray($messageParams['__'] ?? []);
        unset($messageParams['__']);

        // Set contact params
        $systemParams
            ->setContactId($contact->getId())
            ->setContactEmail($contact->getEmail())
            ;

        // Get job data
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $delay  = $recipe->getDefaultDelaySeconds();
        $runAt  = $message->getRunAt() ?? $nowUtc->modify(sprintf('+%d seconds', $delay));
        $stepOrder = $message->getStepOrder() ?? 0;
        $priority = $message->getPriority() ?? $recipe->getDefaultPriority();
        $dedupeKey = $message->getDedupeKey() ?? $this->dedupe->build($recipe, $messageParams, $contact->getId(), $stepOrder, $runAt);
        $flowKey = $message->getFlowKey() ?? $recipe->getFlowKey();
        $flowInstanceId = $message->getFlowInstanceId() ?? $this->flowIdFactory->forRecipe($recipe, $messageParams);
        
        // Get job model
        $jobModel = $this->modelService->getModel(JobModel::class); /** @var \SureLv\Emails\Model\JobModel $jobModel */

        // Check if job with same dedupe key already exists
        $existingJob = $jobModel->getByDedupeKey($dedupeKey);
        if ($existingJob instanceof Job) {
            // Job already exists, skip
            $this->emailsLogger->logWarning('Job with same dedupe key already exists, skipping', [
                'recipe' => $recipeName,
                'dedupe_key' => $dedupeKey,
                'existing_job_id' => $existingJob->getId(),
                'contact_id' => $contact->getId(),
            ]);
            return;
        }

        // Create job
        $job = new Job();
        $job
            ->setName($recipeName)
            ->setKind(JobKind::TRANSACTIONAL)
            ->setParams($messageParams)
            ->setStatus(JobStatus::QUEUED)
            ->setRunAt(\DateTime::createFromImmutable($runAt))
            ->setPriority($priority)
            ->setDedupeKey($dedupeKey)
            ->setFlowKey($flowKey)
            ->setFlowInstanceId($flowInstanceId)
            ->setStepOrder($stepOrder)
            ->setSrcId($message->getSrcId())
            ;
        $job->setSystemParams($systemParams);

        try {
            $jobModel->add($job);
            $this->emailsLogger->logInfo('Job created successfully', [
                'recipe' => $recipeName,
                'job_id' => $job->getId(),
                'contact_id' => $contact->getId(),
                'dedupe_key' => $dedupeKey,
                'run_at' => $runAt->format('Y-m-d H:i:s'),
                'priority' => $priority,
            ]);
        } catch (\Exception $e) {
            $this->emailsLogger->logError('Failed to create job', [
                'recipe' => $recipeName,
                'contact_id' => $contact->getId(),
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
     * Get contact by ID
     * 
     * @param int $contactId
     * @return \SureLv\Emails\Entity\Contact|null
     */
    private function getContactById(int $contactId): ?Contact
    {
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        return $contactModel->getById($contactId);
    }

}
