<?php
declare(strict_types=1);

namespace SureLv\Emails\Provider\List;

use SureLv\Emails\Dto\EmailMessageDto;
use SureLv\Emails\Dto\EmailMessageParamsDto;
use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Model\EmailsListMemberModel;
use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Message\EnqueueListEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailsListModel;
use SureLv\Emails\Model\TypeUnsubscribeModel;
use SureLv\Emails\Service\ModelService;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractListProvider implements ListProviderInterface
{

    protected ModelService $modelService;

    /**
     * @var array<int, string>
     */
    protected array $contact_email_updates = [];

    #[Required]
    public function setModuleDependencies(
        ModelService $modelService,
    ): void {
        $this->modelService = $modelService;
    }

    /**
     * Validate the list job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateListJob(Job $job): void
    {
        $systemParams = $job->getSystemParams();

        // Get list IDs
        if (count($systemParams->getLists()) <= 0) {
            throw new \InvalidArgumentException('Lists are not set');
        }
    }

    /**
     * Prepare the list job
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return void
     */
    public function prepareListJob(Job $job): void
    {
    }

    /**
     * Get list job members
     * 
     * @param \SureLv\Emails\Entity\Job $job
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     * @throws \InvalidArgumentException
     */
    public function getListJobMembers(Job $job): array
    {
        $systemParams = $job->getSystemParams();

        // Add contact email update to the list
        if ($systemParams->getContactId() > 0 && !empty($systemParams->getContactEmail())) {
            $this->contact_email_updates[$systemParams->getContactId()] = $systemParams->getContactEmail();
        }

        if (count($systemParams->getLists()) <= 0) {
            throw new \InvalidArgumentException('Lists are not set');
        }

        $emailsListModel = $this->modelService->getModel(EmailsListModel::class); /** @var \SureLv\Emails\Model\EmailsListModel $emailsListModel */
        $emailsListMemberModel = $this->modelService->getModel(EmailsListMemberModel::class); /** @var \SureLv\Emails\Model\EmailsListMemberModel $emailsListMemberModel */
        
        $params = $job->getParamsWithoutSystemParams();

        $members = [];
        foreach ($systemParams->getLists() as $paramList) {
            // Get list params
            $listId = $paramList->getId();
            $listSubType = $paramList->getSubType();
            
            // Get list
            $list = $emailsListModel->getList($listId);
            if (!$list) {
                throw new \InvalidArgumentException('List #' . $listId . ' not found');
            }

            // Get list members
            $listMembers = [];
            if ($systemParams->getScopeType()) {
                if ($systemParams->getContactId() > 0) {
                    $listMember = $emailsListMemberModel->getListMemberByContactAndScope($list->getId(), $systemParams->getContactId(), $systemParams->getScopeType(), $systemParams->getScopeId() ?? 0, ListMemberStatus::SUBSCRIBED);
                    if ($listMember) {
                        $listMembers = [$listMember];
                    }
                } else {
                    $listMembers = $emailsListMemberModel->getListMembersByScope($list->getId(), $systemParams->getScopeType(), $systemParams->getScopeId() ?? 0, ListMemberStatus::SUBSCRIBED);
                }
            } else {
                $listMembers = $emailsListMemberModel->getListMembersByListId($list->getId(), ListMemberStatus::SUBSCRIBED);
            }

            // Set list to list members
            foreach ($listMembers as $listMember) {
                $listMember->setList($list);
            }

            // Filter list members
            $listMembers = $this->filterListMembers($listMembers, array_merge($params, $paramList->getParams()));
            if ($listSubType) {
                $listMembers = $this->filterListMembersBySubType($listMembers, $listSubType);
            }

            // Merge list members to members
            $members = array_merge($members, $listMembers);
        }

        // Filter members by contact id
        if ($systemParams->getContactId() > 0) {
            $members = $this->filterMembersByContactId($members, $systemParams->getContactId());
        }

        return $this->reviewFilteredMembers($members);
    }

    /**
     * Get contact of list member
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember $member
     * @param string|null &$error
     * @return \SureLv\Emails\Entity\Contact|null
     */
    public function getContactOfListMember(EmailsListMember $member, ?string &$error = null): ?Contact
    {
        if ($member->getContact()) {
            return $member->getContact();
        }
        $contactId = $member->getContactId();
        $email = $this->contact_email_updates[$contactId] ?? null;
        return $this->getContactById($contactId, $email, $error);
    }

    /**
     * Get email message DTO
     * 
     * @param Job $job
     * @param Contact $contact
     * @param EmailsListMember $member
     * @param EmailMessage $emailMessage
     * @return EmailMessageDto
     */
    public function getEmailMessageDto(Job $job, Contact $contact, EmailsListMember $member, EmailMessage $emailMessage): EmailMessageDto
    {
        $dto = new EmailMessageDto($contact, $emailMessage, new EmailMessageParamsDto($job->getParamsWithoutSystemParams()), null, $job, $member);
        if ($member->getList()) {
            $dto->setWithUnsubscribe($member->getList()->getSupportsUnsubscribe());
        }
        $this->updateEmailMessageDto($dto);
        return $dto;
    }

    /**
     * Post job execution
     * 
     * @param Job $job
     * @return void
     */
    public function postJobExecution(Job $job): void
    {
    }

    /**
     * Get the next list message for queue
     * 
     * @param Job $job
     * @param array<string, mixed> $params
     * @return ?EnqueueListEmailMessage
     */
    public function getNextListMessageForQueue(Job $job, array $params = []): ?EnqueueListEmailMessage
    {
        return null;
    }


    /**
     * 
     * PROTECTED METHODS
     * 
     */

    
    /**
     * Filter the list members
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember[] $members
     * @param array<string, mixed> $params
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    protected function filterListMembers(array $members, array $params = []): array
    {
        return $members;
    }

    /**
     * Filter the list members by sub type
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember[] $members
     * @param string $subType
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    protected function filterListMembersBySubType(array $members, string $subType): array
    {
        $typeUnsubscribeModel = $this->modelService->getModel(TypeUnsubscribeModel::class); /** @var \SureLv\Emails\Model\TypeUnsubscribeModel $typeUnsubscribeModel */
        
        $res = [];
        foreach ($members as $member) {
            if ($typeUnsubscribeModel->hasUnsubscribe($member->getContactId(), $member->getScopeType(), $member->getScopeId() ?? 0, $subType)) {
                continue;
            }
            $res[] = $member;
        }
        return $res;
    }

    /**
     * Filter the members by contact id
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember[] $members
     * @param int $contactId
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    protected function filterMembersByContactId(array $members, int $contactId): array
    {
        $contactMembers = [];
        foreach ($members as $member) {
            if ($member->getContactId() == $contactId) {
                $contactMembers[] = $member;
            }
        }
        return $contactMembers;
    }

    /**
     * Review the filtered members
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember[] $members
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    protected function reviewFilteredMembers(array $members): array
    {
        return $members;
    }

    /**
     * Get contact by ID
     * 
     * @param int $contactId
     * @param string|null $email
     * @param string|null &$error
     * @return \SureLv\Emails\Entity\Contact|null
     */
    protected function getContactById(int $contactId, ?string $email = null, ?string &$error = null): ?Contact
    {
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        
        $contact = $contactModel->getById($contactId);
        if (!$contact instanceof Contact) {
            $error = 'Contact not found';
            return null;
        }
        if (!empty($email)) {
            $contact->setEmail($email);
        }
        if (empty($contact->getEmail())) {
            $error = 'Contact email is required';
            return null;
        }
        return $contact;
    }

    /**
     * Update email message DTO
     * 
     * @param EmailMessageDto $dto
     * @return void
     */
    protected function updateEmailMessageDto(EmailMessageDto $dto): void
    {
    }

}
