<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\Tracking;
use SureLv\Emails\Util\DateTimeUtils;

class TrackingModel extends AbstractModel
{

    /**
     * Add tracking
     * 
     * @param \SureLv\Emails\Entity\Tracking $tracking
     * @return bool
     */
    public function add(Tracking $tracking): bool
    {
        $tracking->prePersist();

        try {

            $this->connection->insert($this->tablePrefix . 'tracking', array(
                'hash' => $tracking->getHash(),
                'type' => $tracking->getType()->value,
                'message_id' => $tracking->getMessageId(),
                'context' => json_encode($tracking->getContext()),
                'event_at' => DateTimeUtils::toDbDateTime($tracking->getEventAt()),
                'event_count' => $tracking->getEventCount(),
                'last_event_at' => DateTimeUtils::toDbDateTime($tracking->getLastEventAt()),
                'created_at' => DateTimeUtils::toDbDateTime($tracking->getCreatedAt()),
            ));
            $id = intval($this->connection->lastInsertId());
            if ($id <= 0) {
                return false;
            }
            $tracking->setId($id);
            
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get tracking by ID
     * 
     * @param int $id
     * @return Tracking|null
     */
    public function getById(int $id): ?Tracking
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'tracking WHERE id = :id';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return Tracking::createFromArray($dbRow);
    }

    /**
     * Register event
     * 
     * @param Tracking $tracking
     * @return bool
     */
    public function registerEvent(Tracking $tracking): bool
    {
        $tracking
            ->setEventAt($tracking->getEventAt() ?? new \DateTime())
            ->setEventCount($tracking->getEventCount() + 1)
            ->setLastEventAt(new \DateTime())
            ;
        $sql = '
            UPDATE ' . $this->tablePrefix . 'tracking SET 
                event_at = :event_at, 
                event_count = event_count + 1, 
                last_event_at = :last_event_at
            WHERE id = :id AND hash = :hash
            ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('event_at', DateTimeUtils::toDbDateTime($tracking->getEventAt()));
        $stmt->bindValue('id', $tracking->getId());
        $stmt->bindValue('hash', $tracking->getHash());
        $stmt->bindValue('last_event_at', DateTimeUtils::toDbDateTime($tracking->getLastEventAt()));
        return $stmt->executeQuery()->rowCount() > 0;
    }

}