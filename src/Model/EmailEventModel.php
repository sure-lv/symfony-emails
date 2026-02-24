<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\EmailEvent;
use SureLv\Emails\Util\DateTimeUtils;

class EmailEventModel extends AbstractModel
{
 
    /**
     * Add email event
     * 
     * @param \SureLv\Emails\Entity\EmailEvent $event
     * @return bool
     */
    public function add(EmailEvent $event): bool
    {
        $event->prePersist();

        if (!$event->getEventType()) {
            return false;
        }

        $data = [
            'message_id' => $event->getMessageId(),
            'event_type' => $event->getEventType()->value,
            'payload' => $event->getPayload() ? json_encode($event->getPayload()) : null,
            'occurred_at' => $event->getOccurredAt() ? DateTimeUtils::toDbDateTime($event->getOccurredAt()) : null,
            'created_at' => DateTimeUtils::toDbDateTime($event->getCreatedAt()),
        ];

        $this->connection->insert($this->tablePrefix . 'email_events', $data);
        $id = intval($this->connection->lastInsertId());
        if ($id <= 0) {
            return false;
        }
        $event->setId($id);
        return true;
    }

}