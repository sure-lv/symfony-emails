<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Enum\EmailMessageKind;
use SureLv\Emails\Enum\EmailMessageSendStatus;
use SureLv\Emails\Util\DateTimeUtils;

class EmailMessageModel extends AbstractModel
{
    
    /**
     * Add message
     * 
     * @param \SureLv\Emails\Entity\EmailMessage $emailMessage
     * @return bool
     */
    public function add(EmailMessage $emailMessage): bool
    {
        $emailMessage->prePersist();

        $data = [
            'job_id' => $emailMessage->getJobId(),
            'contact_id' => $emailMessage->getContactId(),
            'subject' => $emailMessage->getSubject(),
            'from_email' => $emailMessage->getFromEmail(),
            'reply_to' => $emailMessage->getReplyTo(),
            'to_email' => $emailMessage->getToEmail(),
            'body_html' => $emailMessage->getBodyHtml(),
            'body_plain' => $emailMessage->getBodyPlain(),
            'headers' => $emailMessage->getHeaders() ? json_encode($emailMessage->getHeaders()) : null,
            'sender_message_id' => $emailMessage->getSenderMessageId(),
            'kind' => $emailMessage->getKind()->value,
            'send_status' => $emailMessage->getSendStatus()->value,
            'sent_at' => $emailMessage->getSentAt() ? DateTimeUtils::toDbDateTime($emailMessage->getSentAt()) : null,
            'failed_at' => $emailMessage->getFailedAt() ? DateTimeUtils::toDbDateTime($emailMessage->getFailedAt()) : null,
            'fail_reason' => $emailMessage->getFailReason(),
            'template_key' => $emailMessage->getTemplateKey(),
            'template_version' => $emailMessage->getTemplateVersion(),
            'render_checksum_html' => $emailMessage->getRenderChecksumHtml(),
            'render_checksum_text' => $emailMessage->getRenderChecksumText(),
            'created_at' => DateTimeUtils::toDbDateTime($emailMessage->getCreatedAt()),
        ];

        // Remove null values for optional fields
        $data = array_filter($data, function($value) {
            return $value !== null;
        });

        try {
            $this->connection->insert($this->tablePrefix . 'messages', $data);
            $id = intval($this->connection->lastInsertId());
            if ($id <= 0) {
                return false;
            }
            $emailMessage->setId($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update message
     * 
     * @param \SureLv\Emails\Entity\EmailMessage $emailMessage
     * @return bool
     */
    public function update(EmailMessage $emailMessage): bool
    {
        $data = [
            'job_id' => $emailMessage->getJobId(),
            'contact_id' => $emailMessage->getContactId(),
            'subject' => $emailMessage->getSubject(),
            'from_email' => $emailMessage->getFromEmail(),
            'reply_to' => $emailMessage->getReplyTo(),
            'to_email' => $emailMessage->getToEmail(),
            'body_html' => $emailMessage->getBodyHtml(),
            'body_plain' => $emailMessage->getBodyPlain(),
            'headers' => $emailMessage->getHeaders() ? json_encode($emailMessage->getHeaders()) : null,
            'sender_message_id' => $emailMessage->getSenderMessageId(),
            'kind' => $emailMessage->getKind()->value,
            'send_status' => $emailMessage->getSendStatus()->value,
            'sent_at' => DateTimeUtils::toDbDateTime($emailMessage->getSentAt()),
            'failed_at' => DateTimeUtils::toDbDateTime($emailMessage->getFailedAt()),
            'fail_reason' => $emailMessage->getFailReason(),
            'template_key' => $emailMessage->getTemplateKey(),
            'template_version' => $emailMessage->getTemplateVersion(),
            'render_checksum_html' => $emailMessage->getRenderChecksumHtml(),
            'render_checksum_text' => $emailMessage->getRenderChecksumText(),
        ];

        // Remove null values for optional fields
        $data = array_filter($data, function($value) {
            return $value !== null;
        });

        try {
            $this->connection->update(
                $this->tablePrefix . 'messages',
                $data,
                ['id' => $emailMessage->getId()]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Pre-allocate email message
     * 
     * @param int $jobId
     * @param int $contactId
     * @param string $toEmail
     * @param EmailMessageKind $kind
     * @return \SureLv\Emails\Entity\EmailMessage|null
     */
    public function preAllocate(int $jobId, int $contactId, string $toEmail, EmailMessageKind $kind = EmailMessageKind::TRANSACTIONAL): ?EmailMessage
    {
        $emailMessage = new EmailMessage();
        $emailMessage
            ->setJobId($jobId)
            ->setContactId($contactId)
            ->setToEmail($toEmail)
            ->setKind($kind)
            ->setSendStatus(EmailMessageSendStatus::QUEUED)
            ->setCreatedAt(new \DateTime())
            ;
        
        $this->connection->insert($this->tablePrefix . 'messages', [
            'job_id' => $emailMessage->getJobId(),
            'contact_id' => $emailMessage->getContactId(),
            'to_email' => $emailMessage->getToEmail(),
            'kind' => $emailMessage->getKind()->value,
            'send_status' => $emailMessage->getSendStatus()->value,
            'created_at' => DateTimeUtils::toDbDateTime($emailMessage->getCreatedAt()),
        ]);
        $id = intval($this->connection->lastInsertId());
        if ($id <= 0) {
            return null;
        }
        $emailMessage->setId($id);
        return $emailMessage;
    }

    /**
     * Get message by ID
     * 
     * @param int $id
     * @return \SureLv\Emails\Entity\EmailMessage|null
     */
    public function getById(int $id): ?EmailMessage
    {
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'messages WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return EmailMessage::createFromArray($dbRow);
    }

    /**
     * Update message as sent
     * 
     * @param int $id
     * @param string $senderMessageId
     * @return bool
     */
    public function updateAsSent(int $id, string $senderMessageId): bool
    {
        $rowsAffected = $this->connection->update($this->tablePrefix . 'messages', [
            'sender_message_id' => $senderMessageId,
            'send_status' => EmailMessageSendStatus::SENT->value,
            'sent_at' => DateTimeUtils::toDbDateTime(new \DateTime()),
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Update message as failed
     * 
     * @param int $id
     * @param string $errorMessage
     * @return bool
     */
    public function updateAsFailed(int $id, string $errorMessage): bool
    {
        $rowsAffected = $this->connection->update($this->tablePrefix . 'messages', [
            'send_status' => EmailMessageSendStatus::FAILED->value,
            'failed_at' => DateTimeUtils::toDbDateTime(new \DateTime()),
            'fail_reason' => $errorMessage,
        ], ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Get message by sender message ID
     * 
     * @param string $senderMessageId
     * @return \SureLv\Emails\Entity\EmailMessage|null
     */
    public function getBySenderMessageId(string $senderMessageId): ?EmailMessage
    {
        if (empty($senderMessageId)) {
            return null;
        }

        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'messages WHERE sender_message_id = :sender_message_id LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('sender_message_id', $senderMessageId);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        return $dbRow ? EmailMessage::createFromArray($dbRow) : null;
    }

    /**
     * Update email message status by message ID
     * 
     * @param int $messageId
     * @param EmailMessageSendStatus $sendStatus
     * @param string|null $failReason
     * @return bool
     */
    public function updateStatusByMessageId(int $messageId, EmailMessageSendStatus $sendStatus, ?string $failReason = null): bool
    {
        if ($messageId <= 0) {
            return false;
        }

        $updateData = [
            'send_status' => $sendStatus->value,
        ];
        if (in_array($sendStatus, [EmailMessageSendStatus::BOUNCED, EmailMessageSendStatus::COMPLAINED, EmailMessageSendStatus::FAILED])) {
            $updateData['failed_at'] = DateTimeUtils::toDbDateTime(new \DateTime());
            $updateData['fail_reason'] = $failReason;
        }

        $rowsAffected = $this->connection->update($this->tablePrefix . 'messages', $updateData, ['id' => $messageId]);
        return $rowsAffected > 0;
    }

}