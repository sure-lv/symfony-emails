<?php

namespace SureLv\Emails\Command;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Enum\EmailMessageKind;
use SureLv\Emails\Enum\JobStatus;
use SureLv\Emails\Exception\JobStatusSkippedException;
use SureLv\Emails\Message\SendEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailMessageModel;
use SureLv\Emails\Model\JobModel;
use SureLv\Emails\Provider\EmailMessage\EmailMessageProviderInterface;
use SureLv\Emails\Provider\List\ListProviderInterface;
use SureLv\Emails\Service\EmailMessageService;
use SureLv\Emails\Service\EmailsListService;
use SureLv\Emails\Service\ModelService;
use SureLv\Emails\Service\RegistryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
	name: 'surelv:emails:send-list-due',
	description: 'Send list due emails',
)]
class SendListDueCommand extends Command
{

    private const MAX_TIME_SECONDS = 60;
    private const MAX_JOBS_PER_ITERATION = 1;

    private ?JobModel $jobModel = null;

    private ?EmailMessageModel $emailMessageModel = null;

    private bool $debug = false;

    private bool $testMode = false;

    private bool $sendEmails = true;
    
    public function __construct(private ModelService $modelService, private EmailsListService $emailsListService, private MessageBusInterface $bus, private UrlGeneratorInterface $urlGenerator, private EmailMessageService $emailMessageService, private RegistryService $registryService)
	{
	}

    protected function configure(): void
    {
        $this->addOption('max-time', null, InputOption::VALUE_OPTIONAL, 'Max time', self::MAX_TIME_SECONDS);
        $this->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'Debug', false);
        $this->addOption('test-draft-job-id', null, InputOption::VALUE_OPTIONAL, 'Test draft job', 0);
        $this->addOption('test-email', null, InputOption::VALUE_OPTIONAL, 'Test email', '');
        $this->addOption('test-list-member-id', null, InputOption::VALUE_OPTIONAL, 'Test list member id', 0);
    }

	protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $jobModel = $this->modelService->getModel(JobModel::class); /** @var \SureLv\Emails\Model\JobModel $jobModel */

        $context = $this->urlGenerator->getContext();
		$context->setHost($this->registryService->getUrlDomain());
		$context->setScheme($this->registryService->getUrlScheme());

        // Debug mode
        $this->debug = (bool)$in->getOption('debug');

        // Test mode (test draft job id)
        $testDraftJob = null;
        $testDraftJobId = (int)$in->getOption('test-draft-job-id');
        if ($testDraftJobId > 0) {
            $testDraftJob = $jobModel->getById($testDraftJobId);
            if (!$testDraftJob) {
                $out->writeln('<error>Test draft job not found</error>');
                return Command::FAILURE;
            }
            if ($testDraftJob->getStatus() !== JobStatus::DRAFT) {
                $out->writeln('<error>Test draft job is not a draft</error>');
                return Command::FAILURE;
            }
            $this->debug = true;
            $this->testMode = true;
        }

        // Test mode (test email contact)
        $testEmailContact = null;
        $testEmail = $in->getOption('test-email');
        if ($testEmail) {
            $testEmailContact = $this->getTestEmailContact($testEmail);
            if (!$testEmailContact) {
                $out->writeln('<error>Test email contact not found</error>');
                return Command::FAILURE;
            }
            $this->debug = true;
            $this->testMode = true;
        }

        // Test mode (test list member id)
        $testListMemberId = (int)$in->getOption('test-list-member-id');
        if ($testListMemberId > 0) {
            $this->debug = true;
            $this->testMode = true;
        } else {
            $testListMemberId = null;
        }

        // If test draft job is set, but no test email contact or test list member id is set, don't send emails
        if ($testDraftJob && !$testEmailContact && !$testListMemberId) {
            $this->sendEmails = false;
        }

        // Max time
        $maxTime = (int)$in->getOption('max-time');
        
        // Debug info
        if ($this->debug && $this->testMode) {
            $out->writeln('<info>Test mode enabled.</info>');
            if ($testDraftJob) {
                $out->writeln('<info>Test draft job: ' . $testDraftJob->getName() . ' (ID: ' . $testDraftJob->getId() . ')</info>');
            }
            if ($testEmailContact) {
                $out->writeln('<info>Test email contact: ' . $testEmailContact->getEmail() . ' (ID: ' . $testEmailContact->getId() . ')</info>');
            }
            if ($testListMemberId) {
                $out->writeln('<info>Test list member id: ' . $testListMemberId . '</info>');
            }
        }
        
        // Start time
        $startTime = microtime(true);

        // Main loop
        while ($maxTime <= 0 || (microtime(true) - $startTime < $maxTime)) {

            // Claim due list jobs
            if ($testDraftJob) {
                $txJobs = [$testDraftJob];
            } else {
                $txJobs = $jobModel->claimDueListJobs(self::MAX_JOBS_PER_ITERATION, 'list-sender', true);
            }

            // If no jobs claimed, sleep and continue
            if (count($txJobs) <= 0) {
                sleep(1);
                continue;
            }

            foreach ($txJobs as $job) {
                
                $provider = null;
                $jobException = null;
                $jobStartTime = (float)microtime(true);

                $membersQty = 0;
                $membersSkipped = 0;
                $membersFailed = 0;
                $successMemberIds = [];
                $failureReasons = [];
                $executionMetaParams = [];

                // Add test email contact id to execution meta params
                if ($this->testMode && $testEmailContact) {
                    $executionMetaParams['test_email_contact_id'] = $testEmailContact->getId();
                }

                try {
                    
                    if ($this->debug) {
                        $out->writeln('Processing job #' . $job->getId() . ' (' . $job->getName() . ')');
                        $out->writeln('Job system params: ' . json_encode($job->getSystemParams()->toArray()));
                        $out->writeln('Job parameters: ' . json_encode($job->getParamsWithoutSystemParams()));
                    }

                    // Get providers
                    $provider = $this->emailsListService->getProvider($job->getName());
                    $emailMessageProvider = $this->emailMessageService->getProvider($job->getName());
                    
                    if ($this->debug) {
                        $out->writeln('List provider: ' . get_class($provider));
                        $out->writeln('Email message provider: ' . get_class($emailMessageProvider));
                    }

                    $provider->validateListJob($job);

                    $provider->prepareListJob($job);

                    $members = $provider->getListJobMembers($job);
                    $membersQty = count($members);

                    if ($this->debug) {
                        $out->writeln('');
                        $out->writeln('Found ' . $membersQty . ' members');
                    }

                    $debugTable = null;
                    if ($this->debug && !$this->sendEmails) {
                        $debugTable = new Table($out);
                        $debugTable->setHeaders([
                            'ID',
                            'Contact ID',
                            'Email',
                            'List ID',
                            'List Name',
                            'Scope Type',
                            'Scope ID',
                            'Status',
                            'Reason',
                        ]);
                    }

                    // Intercept test email contact for each member
                    $memberTestEmailContact = null;
                    if ($this->testMode && $this->sendEmails && $testEmailContact) {
                        $memberTestEmailContact = $testEmailContact;
                        $out->writeln('Intercepting emails to test email contact: ' . $memberTestEmailContact->getEmail());
                    }

                    foreach ($members as $member) {

                        if ($this->testMode && $this->sendEmails && $testListMemberId) {
                            if ($member->getId() !== $testListMemberId) {
                                $membersSkipped++;
                                continue;
                            }
                        }

                        if ($debugTable) {
                            $tableRow = [
                                'id' => $member->getId(),
                                'contact_id' => $member->getContactId(),
                                'email' => $member->getContact() ? $member->getContact()->getEmail() : 'N/A',
                                'list_id' => $member->getListId(),
                                'list_name' => $member->getList() ? $member->getList()->getName() : 'N/A',
                                'scope_type' => $member->getScopeType(),
                                'scope_id' => $member->getScopeId(),
                                'status' => '',
                                'reason' => '',
                            ];
                        }

                        if (!$this->processMember($provider, $emailMessageProvider, $job, $member, $failureReason, $memberTestEmailContact ? clone $memberTestEmailContact : null)) {

                            if ($debugTable) {
                                $tableRow['email'] = $member->getContact() ? $member->getContact()->getEmail() : 'N/A';
                                $tableRow['status'] = 'failed';
                                $tableRow['reason'] = $failureReason ?? 'Unknown reason';
                                $debugTable->addRow(array_values($tableRow));
                            }

                            $membersFailed++;
                            $failureReasons[] = ['id' => $member->getId(), 'reason' => $failureReason ?? 'Unknown reason'];
                            continue;
                        }

                        if ($debugTable) {
                            $tableRow['email'] = $member->getContact() ? $member->getContact()->getEmail() : 'N/A';
                            $tableRow['status'] = 'OK';
                            $debugTable->addRow(array_values($tableRow));
                        }

                        $successMemberIds[] = $member->getId();

                    }

                    // Render debug table
                    if ($debugTable) {
                        $debugTable->render();
                    }

                    // If no email messages dispatched, skip the job
                    if (count($successMemberIds) <= 0) {
                        throw new JobStatusSkippedException('No members processed');
                    }

                } catch (\Throwable $e) {

                    $jobException = $e;

                }

            }

            // Complete job
            $executionMeta = $this->getExecutionMeta($membersQty, $membersSkipped, $membersFailed, $successMemberIds, $failureReasons, $jobStartTime, $executionMetaParams);
            $this->completeJob($job, $provider, $executionMeta, $jobException);

            // Debug info
            if ($this->debug) {
                $out->writeln('');
                if ($jobException) {
                    $out->writeln('<error>Job failed: ' . $jobException->getMessage() . '</error>');
                } else {
                    $out->writeln('<info>Job completed</info>');
                }
                $out->writeln('Job execution meta: ' . json_encode($executionMeta));
            }

            // If test draft job is set, break the loop
            if ($testDraftJob) {
                break;
            }
        }

        if ($this->testMode) {
            $out->writeln('');
            $out->writeln('Add option --test-list-member-id to test a specific list member');
            $out->writeln('Add option --test-email to test a specific email contact');
        }

        return self::SUCCESS;
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Process member
     * 
     * @param ListProviderInterface $provider
     * @param EmailMessageProviderInterface $emailMessageProvider
     * @param Job $job
     * @param EmailsListMember $member
     * @param string|null &$failureReason
     * @param ?Contact $testContact
     * @return bool
     */
    private function processMember(ListProviderInterface $provider, EmailMessageProviderInterface $emailMessageProvider, Job $job, EmailsListMember $member, ?string &$failureReason = null, ?Contact $testContact = null): bool
    {
        $connection = $this->modelService->getConnection();

        try {
        
            // Get contact
            $contact = $provider->getContactOfListMember($member, $error);
            if (!$contact) {
                $failureReason = 'Contact not found';
                return false;
            }
            $member->setContact($contact);

            // Check if contact is suppressed
            if ($contact->isSuppressed()) {
                $failureReason = 'Contact is suppressed';
                return false;
            }

            // If send emails is disabled, return true
            if (!$this->sendEmails) {
                return true;
            }

            // Begin transaction
            $connection->beginTransaction();

            $contactId = $testContact ? $testContact->getId() : $contact->getId();
            $email = $testContact ? $testContact->getEmail() : $contact->getEmail();
        
            // Pre-allocate email message
            $emailMessage = $this->getEmailMessageModel()->preAllocate($job->getId(), $contactId, $email, EmailMessageKind::LIST);
            if (is_null($emailMessage)) {
                throw new \Exception('Failed to pre-allocate email message');
            }

            // Create email message DTO
            $emailMessageDto = $provider->getEmailMessageDto($job, $contact, $member, $emailMessage);

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

            // Commit transaction
            $connection->commit();

            // Publish to RabbitMQ: the consumer will render + SES + update messages row
            $this->bus->dispatch(new SendEmailMessage($emailMessage->getId()));

            // Post-fulfill message
            $emailMessageProvider->postEmailMessageFulfill($emailMessageDto);

        } catch (\Throwable $e) {

            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $failureReason = $e->getMessage();

            return false;

        }

        return true;
    }

    /**
     * Complete job
     * 
     * @param Job $job
     * @param ?ListProviderInterface $provider
     * @param array<string, mixed> $executionMeta
     * @param ?\Throwable $exception
     * @return void
     */
    private function completeJob(Job $job, ?ListProviderInterface $provider = null, array $executionMeta = [], ?\Throwable $exception = null): void
    {
        if ($this->testMode) {
            return;
        }

        // Set job status
        if ($exception) {
            if ($exception instanceof JobStatusSkippedException) {
                $job->setStatus(JobStatus::SKIPPED);
            } else {
                $job->setStatus(JobStatus::FAILED);
            }
        } else {
            $job->setStatus(JobStatus::COMPLETED);
        }

        // Update job status
        if ($job->getStatus() === JobStatus::COMPLETED) {
            $this->getJobModel()->updateStatus($job, $job->getStatus(), null, $executionMeta);
        } elseif ($job->getStatus() === JobStatus::FAILED) {
            $this->getJobModel()->failJob($job->getId(), $exception ? $exception->getMessage() : 'Unknown error', $executionMeta);
        } else {
            $this->getJobModel()->updateStatus($job, $job->getStatus(), $exception ? $exception->getMessage() : null, $executionMeta);
        }

        // Post job execution
        if ($provider) {
            
            // Post job execution
            $provider->postJobExecution($job);

            // If job status is completed, get next email message for queue
            if ($job->getStatus() === JobStatus::COMPLETED && !$job->getSystemParams()->getSkipNextMessage()) {
                $nextListMessage = $provider->getNextListMessageForQueue($job);
                if ($nextListMessage) {
                    $this->bus->dispatch($nextListMessage);
                }
            }

        }
    }

    /**
     * Get execution meta
     * 
     * @param int $membersTotal
     * @param int $membersSkipped
     * @param int $membersFailed
     * @param array<int> $successMemberIds
     * @param array<array{id: int, reason: string}> $failureReasons
     * @param float $startTime
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function getExecutionMeta(int $membersTotal, int $membersSkipped, int $membersFailed, array $successMemberIds, array $failureReasons, float $startTime, array $params = []): array
    {
        $startedAt = new \DateTime();
        $startedAt->setTimestamp((int)$startTime);
        $endedAt = new \DateTime();
        $duration = microtime(true) - $startTime;
        
        return array_merge([
            'members_total' => $membersTotal,
            'members_processed' => count($successMemberIds),
            'members_skipped' => $membersSkipped,
            'members_failed' => $membersFailed,
            'processed_list_member_ids' => $successMemberIds,
            'failure_reasons' => $failureReasons,
            'started_at' => $startedAt->format('c'),
            'completed_at' => $endedAt->format('c'),
            'duration_seconds' => $duration,
        ], $params);
    }

    /**
     * Get test email contact
     * 
     * @param string $testEmail
     * @return \SureLv\Emails\Entity\Contact|null
     */
    private function getTestEmailContact(string $testEmail): ?Contact
    {
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var ContactModel $contactModel */
        $contact = $contactModel->getByEmail($testEmail);
        if (!$contact) {
            return null;
        }
        return $contact;
    }

    /**
     * Get job model
     * 
     * @return JobModel
     */
    private function getJobModel(): JobModel
    {
        if (is_null($this->jobModel)) {
            $model = $this->modelService->getModel(JobModel::class); /** @var JobModel $model */
            $this->jobModel = $model;
        }
        return $this->jobModel;
    }

    /**
     * Get email message model
     * 
     * @return EmailMessageModel
     */
    private function getEmailMessageModel(): EmailMessageModel
    {
        if (is_null($this->emailMessageModel)) {
            $model = $this->modelService->getModel(EmailMessageModel::class); /** @var EmailMessageModel $model */
            $this->emailMessageModel = $model;
        }
        return $this->emailMessageModel;
    }

}