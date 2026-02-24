<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\EmailsListMember;
use SureLv\Emails\Enum\ListMemberStatus;
use SureLv\Emails\Util\DateTimeUtils;

class EmailsListMemberModel extends AbstractModel
{

    /**
     * Add list member
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember $member
     * @param bool $updateIfExists
     * @return bool
     */
    public function add(EmailsListMember $member, bool $updateIfExists = true): bool
    {
        $currentMember = $this->getListMemberByContactAndScope($member->getListId(), $member->getContactId(), $member->getScopeType(), $member->getScopeId());
        if ($currentMember) {
            $member->setId($currentMember->getId());
            if ($updateIfExists) {
                $fieldsToUpdate = [];

                // Update params
                $fieldsToUpdate[] = 'params';
                $params = array_merge($currentMember->getParams(), $member->getParams());
                $member->setParams($params);

                // Update data
                $fieldsToUpdate[] = 'data';
                $data = array_merge($currentMember->getData() ?? [], $member->getData() ?? []);
                if (count($data) <= 0) {
                    $data = null;
                }
                $member->setData($data);

                // Update status
                if ($member->getStatus() !== $currentMember->getStatus()) {
                    $fieldsToUpdate[] = 'status';
                    if ($member->getStatus() === ListMemberStatus::SUBSCRIBED) {
                        $fieldsToUpdate[] = 'subscribed_at';
                        $fieldsToUpdate[] = 'source';
                    } elseif ($member->getStatus() === ListMemberStatus::UNSUBSCRIBED) {
                        $fieldsToUpdate[] = 'unsubscribed_at';
                    }
                }

                // Update list member
                $this->update($member, $fieldsToUpdate);
            }
            return true;
        }
        
        $member->prePersist();

        $this->connection->insert($this->tablePrefix . 'list_members', array(
            'list_id' => $member->getListId(),
            'contact_id' => $member->getContactId(),
            'scope_type' => $member->getScopeType(),
            'scope_id' => $member->getScopeId(),
            'status' => $member->getStatus()->value,
            'source' => $member->getSource(),
            'params' => json_encode($member->getParams()),
            'data' => $member->getData() ? json_encode($member->getData()) : null,
            'subscribed_at' => DateTimeUtils::toDbDateTime($member->getSubscribedAt()),
            'unsubscribed_at' => DateTimeUtils::toDbDateTime($member->getUnsubscribedAt()),
        ));

        $id = intval($this->connection->lastInsertId());
        if ($id <= 0) {
            return false;
        }
        $member->setId($id);
        return true;
    }

    /**
     * Update list member
     * 
     * @param \SureLv\Emails\Entity\EmailsListMember $member
     * @param array<string> $fieldsToUpdate
     * @return bool
     */
    public function update(EmailsListMember $member, array $fieldsToUpdate = []): bool
    {
        if ($member->getId() <= 0) {
            return false;
        }
        if (count($fieldsToUpdate) <= 0) {
            $fieldsToUpdate = [
                'status',
                'source',
                'params',	
                'data',
                'subscribed_at',
                'unsubscribed_at',
            ];
        }
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field) {
            switch ($field) {
                case 'status':
                    $dataToUpdate[$field] = $member->getStatus()->value;
                    break;
                case 'source':
                    $dataToUpdate[$field] = $member->getSource();
                    break;
                case 'params':
                    $dataToUpdate[$field] = json_encode($member->getParams());
                    break;
                case 'data':
                    $dataToUpdate[$field] = $member->getData() ? json_encode($member->getData()) : null;
                    break;
                case 'subscribed_at':
                    $dataToUpdate[$field] = DateTimeUtils::toDbDateTime($member->getSubscribedAt());
                    break;
                case 'unsubscribed_at':
                    $dataToUpdate[$field] = DateTimeUtils::toDbDateTime($member->getUnsubscribedAt());
                    break;
            }
        }
        if (count($dataToUpdate) <= 0) {
            return false;
        }
        try {
            $this->connection->update($this->tablePrefix . 'list_members', $dataToUpdate, array('id' => $member->getId()));
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get list member by id
     * 
     * @param int $id
     * @return \SureLv\Emails\Entity\EmailsListMember|null
     */
    public function getListMemberById(int $id): ?EmailsListMember
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'list_members WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return EmailsListMember::createFromArray($dbRow);
    }

    /**
     * Get list member by contact and scope
     * 
     * @param int $listId
     * @param int $contactId
     * @param string $scopeType
     * @param ?int $scopeId
     * @param ?ListMemberStatus $status
     * @return \SureLv\Emails\Entity\EmailsListMember|null
     */
    public function getListMemberByContactAndScope(int $listId, int $contactId, string $scopeType, ?int $scopeId = null, ?ListMemberStatus $status = null): ?EmailsListMember
    {
        $sqlWhereStatus = '';
        if ($status) {
            $sqlWhereStatus = ' AND status = :status';
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'list_members WHERE list_id = :list_id AND scope_type = :scope_type AND scope_id = :scope_id AND contact_id = :contact_id' . $sqlWhereStatus . ' LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('list_id', $listId);
        $stmt->bindValue('scope_type', $scopeType);
        $stmt->bindValue('scope_id', $scopeId);
        $stmt->bindValue('contact_id', $contactId);
        if ($status) {
            $stmt->bindValue('status', $status->value);
        }
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return EmailsListMember::createFromArray($dbRow);
    }

    /**
     * Get list members by scope
     * 
     * @param int $listId
     * @param string $scopeType
     * @param int $scopeId
     * @param ?ListMemberStatus $status
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    public function getListMembersByScope(int $listId, string $scopeType, int $scopeId, ?ListMemberStatus $status = null): array
    {
        $sqlWhereStatus = '';
        if ($status) {
            $sqlWhereStatus = ' AND status = :status';
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'list_members WHERE list_id = :list_id AND scope_type = :scope_type AND scope_id = :scope_id' . $sqlWhereStatus;
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('list_id', $listId);
        $stmt->bindValue('scope_type', $scopeType);
        $stmt->bindValue('scope_id', $scopeId);
        if ($status) {
            $stmt->bindValue('status', $status->value);
        }
        $dbRes = $stmt->executeQuery();
        $dbRows = $dbRes->fetchAllAssociative();
        return array_map(function(array $dbRow) {
            return EmailsListMember::createFromArray($dbRow);
        }, $dbRows);
    }

    /**
     * Get list members by list id and status
     * 
     * @param int $listId
     * @param ?ListMemberStatus $status
     * @return \SureLv\Emails\Entity\EmailsListMember[]
     */
    public function getListMembersByListId(int $listId, ?ListMemberStatus $status = null): array
    {
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'list_members WHERE list_id = :list_id';
        if ($status) {
            $sql .= ' AND status = :status';
        }
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('list_id', $listId);
        if ($status) {
            $stmt->bindValue('status', $status->value);
        }
        $dbRes = $stmt->executeQuery();
        $dbRows = $dbRes->fetchAllAssociative();
        return array_map(function(array $dbRow) {
            return EmailsListMember::createFromArray($dbRow);
        }, $dbRows);
    }

}