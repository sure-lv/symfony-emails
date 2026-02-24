<?php

namespace SureLv\Emails\Provider\EmailMessage;

use SureLv\Emails\Dto\EmailMessageDto;
use SureLv\Emails\Dto\EmailMessageParamsDto;
use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Message\EnqueueTransactionalEmailMessage;
use SureLv\Emails\Service\EmailTrackingService;

interface EmailMessageProviderInterface
{
    
    /**
     * Initialize the provider
     * 
     * @param array<string, mixed> $config
     * @return void
     */
    public function initProvider(array $config): void;

    public function setEmailTrackingService(EmailTrackingService $emailTrackingService): void;

    public function fulfillEmailMessage(EmailMessageDto $emailMessageDto, ?string &$error = null): bool;

    public function isValidFulfilledMessage(EmailMessage $emailMessage, ?string &$error = null): bool;

    public function saveMessage(EmailMessage $emailMessage): void;

    public function postEmailMessageFulfill(EmailMessageDto $emailMessageDto): void;

    public function getNextTransactionalEmailMessageForQueue(Job $job, EmailMessageParamsDto $paramsDto): ?EnqueueTransactionalEmailMessage;

}