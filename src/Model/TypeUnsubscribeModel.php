<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\TypeUnsubscribe;
use SureLv\Emails\Util\DateTimeUtils;

final class TypeUnsubscribeModel extends AbstractModel
{

    /**
     * Has unsubscribe
     * 
     * @param int $contactId
     * @param string $scopeType
     * @param int $scopeId
     * @param string $emailType
     * @return bool
     */
    public function hasUnsubscribe(int $contactId, string $scopeType, int $scopeId, string $emailType): bool
    {
        $sql = '
            SELECT id
            FROM ' . $this->tablePrefix . 'type_unsubscribes
            WHERE contact_id = :contactId AND scope_type = :scopeType AND scope_id = :scopeId AND email_type = :emailType
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('contactId', $contactId);
        $stmt->bindValue('scopeType', $scopeType);
        $stmt->bindValue('scopeId', $scopeId);
        $stmt->bindValue('emailType', $emailType);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (is_array($dbRow)) {
            return true;
        }
        return false;
    }

    /**
     * Add type unsubscribe
     * 
     * @param \SureLv\Emails\Entity\TypeUnsubscribe $typeUnsubscribe
     * @return void
     */
    public function add(TypeUnsubscribe $typeUnsubscribe): void
    {
        $typeUnsubscribe->prePersist();
        $sql = '
            INSERT IGNORE INTO ' . $this->tablePrefix . 'type_unsubscribes (contact_id, scope_type, scope_id, email_type, created_at) VALUES (:contactId, :scopeType, :scopeId, :emailType, :createdAt)
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('contactId', $typeUnsubscribe->getContactId());
        $stmt->bindValue('scopeType', $typeUnsubscribe->getScopeType());
        $stmt->bindValue('scopeId', $typeUnsubscribe->getScopeId());
        $stmt->bindValue('emailType', $typeUnsubscribe->getEmailType());
        $stmt->bindValue('createdAt', DateTimeUtils::toDbDateTime($typeUnsubscribe->getCreatedAt()));
        $stmt->executeStatement();
        if ($this->connection->lastInsertId() <= 0) {
            return;
        }
        $typeUnsubscribe->setId(intval($this->connection->lastInsertId()));
    }

    /**
     * Remove type unsubscribe
     * 
     * @param int $contactId
     * @param string $scopeType
     * @param int $scopeId
     * @param string $emailType
     * @return void
     */
    public function remove(int $contactId, string $scopeType, int $scopeId, string $emailType): void
    {
        $sql = '
            DELETE FROM ' . $this->tablePrefix . 'type_unsubscribes
            WHERE contact_id = :contactId AND scope_type = :scopeType AND scope_id = :scopeId AND email_type = :emailType
        ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('contactId', $contactId);
        $stmt->bindValue('scopeType', $scopeType);
        $stmt->bindValue('scopeId', $scopeId);
        $stmt->bindValue('emailType', $emailType);
        $stmt->executeStatement();
    }

}