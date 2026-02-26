<?php
declare(strict_types=1);

namespace SureLv\Emails\Provider\List;

use SureLv\Emails\Dto\EmailMessageDto;
use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Message\EnqueueListEmailMessage;

interface ListProviderInterface
{

    /**
     * Validate the list job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateListJob(Job $job): void;

    /**
     * Prepare the list job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return void
     */
    public function prepareListJob(Job $job): void;

    /**
     * Get the list job members
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     * @throw \InvalidArgumentException
     */
    public function getListJobMembers(Job $job): array;

    /**
     * Get contact of list member
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember $member
     * @param string|null &$error
     * @return \SureLv\Emails\Entity\Contact|null
     */
    public function getContactOfListMember(EmailsListMember $member, ?string &$error = null): ?Contact;

    /**
     * Get email message DTO
     * 
     * @param Job $job
     * @param Contact $contact
     * @param EmailsListMember $member
     * @param EmailMessage $emailMessage
     * @return EmailMessageDto
     */
    public function getEmailMessageDto(Job $job, Contact $contact, EmailsListMember $member, EmailMessage $emailMessage): EmailMessageDto;

    /**
     * Post job execution
     * 
     * @param Job $job
     * @return void
     */
    public function postJobExecution(Job $job): void;

    /**
     * Get the next list message for queue
     * 
     * @param Job $job
     * @param array<string, mixed> $params
     * @return ?EnqueueListEmailMessage
     */
    public function getNextListMessageForQueue(Job $job, array $params = []): ?EnqueueListEmailMessage;

}
