<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Dto\ListMemberStatusChangeDto;
use SureLv\Emails\Entity\EmailEvent;
use SureLv\Emails\Enum\EmailEventType;
use SureLv\Emails\Message\MemberStatusChangeMessageInterface;
use SureLv\Emails\Model\EmailEventModel;

class EmailEventService
{

    public function __construct(private ModelService $modelService, private RegistryService $registryService)
    {
    }

    /**
     * Register email event
     * 
     * @param int $messageId
     * @param EmailEventType $eventType
     * @param array<string, mixed> $payload
     * @param ?\DateTime $occurredAt
     * @return bool
     */
    public function register(int $messageId, EmailEventType $eventType, array $payload = [], ?\DateTime $occurredAt = null): bool
    {
        $event = new EmailEvent();
        $event->setMessageId($messageId);
        $event->setEventType($eventType);
        $event->setPayload($payload);
        $event->setOccurredAt($occurredAt);

        $model = $this->modelService->getModel(EmailEventModel::class); /** @var \SureLv\Emails\Model\EmailEventModel $model */

        return $model->add($event);
    }

    /**
     * Get list member status change message
     * 
     * @param ListMemberStatusChangeDto $listMemberStatusChangeDto
     * @return ?MemberStatusChangeMessageInterface
     */
    public function getListMemberStatusChangeMessage(ListMemberStatusChangeDto $listMemberStatusChangeDto): ?MemberStatusChangeMessageInterface
    {
        $messageOnListMemberStatusChange = $this->registryService->getMessageOnListMemberStatusChange();
        if (!$messageOnListMemberStatusChange) {
            return null;
        }
        if (!class_exists($messageOnListMemberStatusChange)) {
            return null;
        }

        try {
            $reflectionClass = new \ReflectionClass($messageOnListMemberStatusChange);
            if (!$reflectionClass->implementsInterface(MemberStatusChangeMessageInterface::class)) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $message = new $messageOnListMemberStatusChange(
            $listMemberStatusChangeDto->getToStatus()->value, 
            $listMemberStatusChangeDto->getFromStatus() ? $listMemberStatusChangeDto->getFromStatus()->value : null, 
            $listMemberStatusChangeDto->getSubType(), 
            $listMemberStatusChangeDto->getScopeType(), 
            $listMemberStatusChangeDto->getScopeId(), 
            $listMemberStatusChangeDto->getParams(),
            [
                'list_member_id' => $listMemberStatusChangeDto->getListMemberId(),
                'contact_id' => $listMemberStatusChangeDto->getContactId(),
                'list_id' => $listMemberStatusChangeDto->getListId(),
                'occurred_at' => $listMemberStatusChangeDto->getOccurredAt(),
            ]
        ); /** @var \SureLv\Emails\Message\MemberStatusChangeMessageInterface $message */
        return $message;
    }

}