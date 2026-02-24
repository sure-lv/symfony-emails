<?php

namespace SureLv\Emails\Command;

use SureLv\Emails\Dto\EmailMessageDto;
use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Exception\JobStatusSkippedException;
use SureLv\Emails\Message\SendEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailMessageModel;
use SureLv\Emails\Model\JobModel;
use SureLv\Emails\Service\EmailMessageService;
use SureLv\Emails\Service\ModelService;
use SureLv\Emails\Service\RegistryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
	name: 'surelv:emails:send-transactional-due',
	description: 'Send transactional due emails',
)]
class SendTransactionalDueCommand extends Command
{

    private const MAX_TIME_SECONDS = 60;
    private const MAX_JOBS_PER_ITERATION = 200;
    
    public function __construct(private ModelService $modelService, private EmailMessageService $emailMessageService, private MessageBusInterface $bus, private UrlGeneratorInterface $urlGenerator, private RegistryService $registryService)
	{
        parent::__construct();
	}

    protected function configure(): void
    {
        $this->addOption('max-time', null, InputOption::VALUE_OPTIONAL, 'Max time', self::MAX_TIME_SECONDS);
    }

	protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $connection = $this->modelService->getConnection();
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        $jobModel = $this->modelService->getModel(JobModel::class); /** @var \SureLv\Emails\Model\JobModel $jobModel */
        $emailMessageModel = $this->modelService->getModel(EmailMessageModel::class); /** @var \SureLv\Emails\Model\EmailMessageModel $emailMessageModel */

        $context = $this->urlGenerator->getContext();
		$context->setHost($this->registryService->getUrlDomain());
		$context->setScheme($this->registryService->getUrlScheme());

        $maxTime = (int)$in->getOption('max-time');
        $startTime = microtime(true);

        while ($maxTime <= 0 || (microtime(true) - $startTime < $maxTime)) {
            $txJobs = $jobModel->claimDueTransactionalJobs(self::MAX_JOBS_PER_ITERATION, 'sender', true);

            if (count($txJobs) <= 0) {
                sleep(1);
                continue;
            }

            foreach ($txJobs as $job) {
                try {
                    $systemParams = $job->getSystemParams();

                    // Get contact
                    $contact = $contactModel->getById((int)($systemParams->getContactId() ?? 0));
                    if (!$contact instanceof Contact) {
                        throw new \Exception('Contact not found');
                    }

                    // Update contact email if set
                    if (!empty($systemParams->getContactEmail())) {
                        $contact->setEmail($systemParams->getContactEmail());
                    }

                    // Check if to email is set
                    if (empty($contact->getEmail())) {
                        throw new \Exception('To email is required');
                    }

                    // Check if contact is suppressed
                    if ($contact->isSuppressed()) {
                        throw new \Exception('Contact is suppressed');
                    }

                    $connection->beginTransaction();
                    
                    // Pre-allocate email message
                    $emailMessage = $emailMessageModel->preAllocate($job->getId(), $contact->getId(), $contact->getEmail());
                    if (is_null($emailMessage)) {
                        throw new \Exception('Failed to pre-allocate email message');
                    }

                    // Get provider
                    $emailMessageProvider = $this->emailMessageService->getProvider($job->getName());

                    // Create email message data
                    $emailMessageDto = new EmailMessageDto($contact, $emailMessage, null, $job->getParamsWithoutSystemParams(), $job);

                    // Fulfill message
                    $success = $emailMessageProvider->fulfillEmailMessage($emailMessageDto, $error);
                    if (!$success) {
                        throw new \Exception($error ?? 'Failed to fulfill email message');
                    }

                    // Validate fulfilled message
                    if (!$emailMessageProvider->isValidFulfilledMessage($emailMessage, $error)) {
                        throw new \Exception($error ?? 'Invalid fulfilled email message');
                    }

                    // Save message
                    $emailMessageProvider->saveMessage($emailMessage);

                    // Update job status to completed
                    $jobModel->updateStatus($job, JobStatus::COMPLETED);

                    // Commit transaction
                    $connection->commit();

                    // Publish to RabbitMQ: the consumer will render + SES + update messages row
                    $this->bus->dispatch(new SendEmailMessage($emailMessage->getId()));

                    // Post-fulfill message
                    $emailMessageProvider->postEmailMessageFulfill($emailMessageDto);

                    // Get next email message for queue
                    $nextTransactionalMessage = $emailMessageProvider->getNextTransactionalEmailMessageForQueue($job, $emailMessageDto->getParamsDto());
                    if ($nextTransactionalMessage) {
                        $this->bus->dispatch($nextTransactionalMessage);
                    }

                } catch (\Throwable $e) {
                    if ($connection->isTransactionActive()) {
                        $connection->rollBack();
                    }

                    if ($e instanceof JobStatusSkippedException) {
                        $jobModel->updateStatus($job, JobStatus::SKIPPED, $e->getMessage());
                    } else {
                        $jobModel->failJob($job->getId(), $e->getMessage());
                    }
                }
            }
        }

        return self::SUCCESS;
    }

}