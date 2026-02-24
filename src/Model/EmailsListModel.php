<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Util\DateTimeUtils;

class EmailsListModel extends AbstractModel
{
    
    /**
     * Get list by id
     * 
     * @param int $id
     * @return \SureLv\Emails\Entity\EmailsList|null
     */
    public function getList(int $id): ?EmailsList
    {
        if ($id <= 0) {
            return null;
        }
        $sql = '
            SELECT *
            FROM ' . $this->tablePrefix . 'lists
            WHERE id = :id
            ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return EmailsList::createFromArray($dbRow);
    }

    /**
     * Get list by name
     * 
     * @param string $name
     * @return \SureLv\Emails\Entity\EmailsList|null
     */
    public function getListByName(string $name): ?EmailsList
    {
        if (empty($name)) {
            return null;
        }
        $sql = '
            SELECT *
            FROM ' . $this->tablePrefix . 'lists
            WHERE name = :name
            ';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('name', $name);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        $list = EmailsList::createFromArray($dbRow);
        return $list;
    }

    /**
     * Add list
     * 
     * @param \SureLv\Emails\Entity\EmailsList $emailsList
     * @return bool
     */
    public function add(EmailsList $emailsList): bool
    {
        $emailsList->prePersist();
        $this->connection->insert($this->tablePrefix . 'lists', [
            'name' => $emailsList->getName(),
            'title' => $emailsList->getTitle(),
            'scope_type' => $emailsList->getScopeType(),
            'supports_unsubscribe' => $emailsList->getSupportsUnsubscribe() ? 1 : 0,
            'provider_class' => $emailsList->getProviderClass(),
            'created_at' => DateTimeUtils::toDbDateTime($emailsList->getCreatedAt()),
        ]);
        if ($this->connection->lastInsertId() <= 0) {
            return false;
        }
        $emailsList->setId(intval($this->connection->lastInsertId()));
        return true;
    }

    /**
     * Update list
     * 
     * @param \SureLv\Emails\Entity\EmailsList $emailsList
     * @param array<string> $fieldsToUpdate
     * @return bool
     */
    public function update(EmailsList $emailsList, array $fieldsToUpdate = []): bool
    {
        if ($emailsList->getId() <= 0) {
            return false;
        }
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field) {
            switch ($field) {
                case 'title':
                    $dataToUpdate[$field] = $emailsList->getTitle();
                    break;
                case 'scope_type':
                    $dataToUpdate[$field] = $emailsList->getScopeType();
                    break;
                case 'supports_unsubscribe':
                    $dataToUpdate[$field] = $emailsList->getSupportsUnsubscribe() ? 1 : 0;
                    break;
                case 'provider_class':
                    $dataToUpdate[$field] = $emailsList->getProviderClass();
                    break;
            }
        }
        if (count($dataToUpdate) <= 0) {
            return false;
        }
        return $this->connection->update($this->tablePrefix . 'lists', $dataToUpdate, ['id' => $emailsList->getId()]) > 0;
    }

    /**
     * Get all lists
     * 
     * @return EmailsList[]
     */
    public function getAllLists(): array
    {
        $sql = '
            SELECT *
            FROM ' . $this->tablePrefix . 'lists
            ORDER BY id ASC
        ';
        $stmt = $this->connection->prepare($sql);
        $dbRes = $stmt->executeQuery();
        $dbRows = $dbRes->fetchAllAssociative();
        $lists = [];
        foreach ($dbRows as $dbRow) {
            $lists[] = EmailsList::createFromArray($dbRow);
        }
        return $lists;
    }

}