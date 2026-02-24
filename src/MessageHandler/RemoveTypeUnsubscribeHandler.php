<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Message\RemoveTypeUnsubscribeMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\TypeUnsubscribeModel;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveTypeUnsubscribeHandler
{

    public function __construct(
        private ModelService $modelService
    ) { }

    public function __invoke(RemoveTypeUnsubscribeMessage $message): void
    {        
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        $typeUnsubscribeModel = $this->modelService->getModel(TypeUnsubscribeModel::class); /** @var \SureLv\Emails\Model\TypeUnsubscribeModel $typeUnsubscribeModel */
        
        // Get contact id
        $contactId = $message->getContactId();
        if (!$contactId && $message->getEmail()) {
            $contact = $contactModel->getByEmail($message->getEmail());
            if ($contact) {
                $contactId = $contact->getId();
            }
        }

        // Check if contact id is found
        if (!$contactId) {
            throw new \Exception('Contact not found: ' . $message->getEmail());
        }

        if (!$typeUnsubscribeModel->hasUnsubscribe($contactId, $message->getScopeType(), $message->getScopeId(), $message->getEmailType())) {
            return;
        }

        $typeUnsubscribeModel->remove($contactId, $message->getScopeType(), $message->getScopeId(), $message->getEmailType());
    }

}
