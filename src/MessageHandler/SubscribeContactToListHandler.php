<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Message\SubscribeContactToListMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailsListMemberModel;
use SureLv\Emails\Model\EmailsListModel;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SubscribeContactToListHandler
{

    public function __construct(
        private ModelService $modelService
    ) { }

    public function __invoke(SubscribeContactToListMessage $message): void
    {        
        if (!$message->getListId() && !$message->getListName()) {
            throw new \Exception('List ID or list name is required');
        }

        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        $emailsListModel = $this->modelService->getModel(EmailsListModel::class); /** @var \SureLv\Emails\Model\EmailsListModel $emailsListModel */
        $emailsListMemberModel = $this->modelService->getModel(EmailsListMemberModel::class); /** @var \SureLv\Emails\Model\EmailsListMemberModel $emailsListMemberModel */
        
        // Get contact id
        $contactId = $message->getContactId();
        if (!$contactId && $message->getEmail()) {
            $contact = $contactModel->getByEmail($message->getEmail());
            if (!$contact instanceof Contact) {
                $contact = Contact::createFromEmail($message->getEmail());
                if ($contactModel->add($contact)) {
                    $contactId = $contact->getId();
                }
            } else {
                $contactId = $contact->getId();
            }
        }

        // Check if contact id is found
        if (!$contactId) {
            throw new \Exception('Contact not found: ' . $message->getEmail());
        }

        // Get list id
        $listId = $message->getListId();
        if (!$listId) {
            $emailsList = $emailsListModel->getListByName($message->getListName() ?? '');
            if (!$emailsList instanceof EmailsList) {
                throw new \Exception('List not found: ' . $message->getListName());
            }
            $listId = $emailsList->getId();
        }

        // Get subscribed at and unsubscribed at
        $subscribedAt = $message->getSubscribedAt() ?? (new \DateTime());
        $unsubscribedAt = $message->getUnsubscribedAt();
        if ($message->getStatus() === ListMemberStatus::UNSUBSCRIBED && !$unsubscribedAt) {
            $unsubscribedAt = new \DateTime();
        }

        // If status is unsubscribed, check if contact exists in list, if not, return
        if ($message->getStatus() === ListMemberStatus::UNSUBSCRIBED) {
            $currentMember = $emailsListMemberModel->getListMemberByContactAndScope($listId, $contactId, $message->getScopeType(), $message->getScopeId());
            if (!$currentMember instanceof EmailsListMember) {
                return;
            }
        }

        // Create emails list member
        $emailsListMember = new EmailsListMember();
        $emailsListMember
            ->setListId($listId)
            ->setContactId($contactId)
            ->setScopeType($message->getScopeType())
            ->setScopeId($message->getScopeId())
            ->setStatus($message->getStatus())
            ->setSource($message->getSource())
            ->setParams($message->getParams())
            ->setData($message->getData())
            ->setSubscribedAt($subscribedAt)
            ->setUnsubscribedAt($unsubscribedAt)
            ;

        // Add emails list member
        $emailsListMemberModel->add($emailsListMember);
    }

}
