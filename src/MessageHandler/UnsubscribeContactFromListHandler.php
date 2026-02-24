<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Message\UnsubscribeContactFromListMessage;
use SureLv\Emails\Model\EmailsListMemberModel;
use SureLv\Emails\Model\EmailsListModel;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UnsubscribeContactFromListHandler
{

    public function __construct(
        private ModelService $modelService
    ) { }

    public function __invoke(UnsubscribeContactFromListMessage $message): void
    {
        if (!$message->getListId() && !$message->getListName()) {
            throw new \Exception('List ID or list name is required');
        }

        $emailsListModel = $this->modelService->getModel(EmailsListModel::class); /** @var \SureLv\Emails\Model\EmailsListModel $emailsListModel */
        $emailsListMemberModel = $this->modelService->getModel(EmailsListMemberModel::class); /** @var \SureLv\Emails\Model\EmailsListMemberModel $emailsListMemberModel */
        
        // Get list id
        $listId = $message->getListId();
        if (!$listId) {
            $emailsList = $emailsListModel->getListByName($message->getListName() ?? '');
            if (!$emailsList instanceof EmailsList) {
                throw new \Exception('List not found: ' . $message->getListName());
            }
            $listId = $emailsList->getId();
        }

        // Get unsubscribed at
        $unsubscribedAt = $message->getUnsubscribedAt() ?? (new \DateTime());

        // Create emails list member
        $emailsListMember = new EmailsListMember();
        $emailsListMember
            ->setListId($listId)
            ->setContactId($message->getContactId())
            ->setScopeType($message->getScopeType())
            ->setScopeId($message->getScopeId())
            ->setStatus(ListMemberStatus::UNSUBSCRIBED)
            ->setUnsubscribedAt($unsubscribedAt)
            ;

        // Add emails list member
        $emailsListMemberModel->add($emailsListMember);
    }

}
