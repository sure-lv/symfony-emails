<?php

namespace SureLv\Emails\Model;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Util\DateTimeUtils;
use SureLv\Emails\Util\EmailUtils;

class ContactModel extends AbstractModel
{
    
    /**
     * Add contact
     * 
     * @param \SureLv\Emails\Entity\Contact $contact
     * @param array<string> $fieldsToUpdate
     * @return bool
     */
    public function add(Contact $contact, array $fieldsToUpdate = []): bool
    {
        if ($contact->getEmailNorm() === '') {
            $contact->setEmailNorm(EmailUtils::normalizeEmail($contact->getEmail()));
        }
        
        $currentContact = $this->getByEmailNorm($contact->getEmailNorm());
        if ($currentContact instanceof Contact) {
            $contact->setId($currentContact->getId());
            $this->updateContactRecord($contact, $fieldsToUpdate);
            return true;
        }
        
        $contact->prePersist();

        $this->connection->insert($this->tablePrefix . 'contacts', array(
            'email_norm' => $contact->getEmailNorm(),
            'email' => $contact->getEmail(),
            'first_name' => $contact->getFirstName(),
            'last_name' => $contact->getLastName(),
            'is_verified' => $contact->getIsVerified() ? 1 : 0,
            'suppressed_until' => DateTimeUtils::toDbDateTime($contact->getSuppressedUntil()),
            'suppression_reason' => $contact->getSuppressionReason() ? $contact->getSuppressionReason()->value : null,
            'created_at' => DateTimeUtils::toDbDateTime($contact->getCreatedAt()),
        ));

        $id = intval($this->connection->lastInsertId());
        if ($id <= 0) {
            return false;
        }
        $contact->setId($id);
        return true;
    }

    /**
     * Get contact by email
     * 
     * @param string $email
     * @return \SureLv\Emails\Entity\Contact|null
     */
    public function getByEmail(string $email): ?Contact
    {
        $email = EmailUtils::normalizeEmail($email);
        return $this->getByEmailNorm($email);
    }

    /**
     * Get contact by email norm
     * 
     * @param string $emailNorm
     * @return \SureLv\Emails\Entity\Contact|null
     */
    public function getByEmailNorm(string $emailNorm): ?Contact
    {
        if (empty($emailNorm)) {
            return null;
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'contacts WHERE email_norm = :email_norm LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('email_norm', $emailNorm);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return Contact::createFromArray($dbRow);
    }

    /**
     * Get contact by ID
     * 
     * @param int $id
     * @return \SureLv\Emails\Entity\Contact|null
     */
    public function getById(int $id): ?Contact
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'contacts WHERE id = :id LIMIT 1';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $dbRes = $stmt->executeQuery();
        $dbRow = $dbRes->fetchAssociative();
        if (!is_array($dbRow)) {
            return null;
        }
        return Contact::createFromArray($dbRow);
    }

    /**
     * Update contact record
     * 
     * @param \SureLv\Emails\Entity\Contact $contact
     * @param array<string> $fieldsToUpdate
     * @return bool
     */
    public function updateContactRecord(Contact $contact, array $fieldsToUpdate): bool
    {
        $data = [];
        foreach ($fieldsToUpdate as $field) {
            switch ($field) {
                case 'email':
                    $data['email'] = $contact->getEmail();
                    break;
                case 'first_name':
                    $data['first_name'] = $contact->getFirstName();
                    break;
                case 'last_name':
                    $data['last_name'] = $contact->getLastName();
                    break;
                case 'is_verified':
                    $data['is_verified'] = $contact->getIsVerified() ? 1 : 0;
                    break;
                case 'suppressed_until':
                    $data['suppressed_until'] = DateTimeUtils::toDbDateTime($contact->getSuppressedUntil());
                    break;
                case 'suppression_reason':
                    $data['suppression_reason'] = $contact->getSuppressionReason() ? $contact->getSuppressionReason()->value : null;
                    break;
                case 'last_email_at':
                    $data['last_email_at'] = DateTimeUtils::toDbDateTime($contact->getLastEmailAt());
                    break;
                case 'bounce_type':
                    $data['bounce_type'] = $contact->getBounceType();
                    break;
                case 'bounce_subtype':
                    $data['bounce_subtype'] = $contact->getBounceSubtype();
                    break;
                case 'bounce_diagnostic_code':
                    $data['bounce_diagnostic_code'] = $contact->getBounceDiagnosticCode();
                    break;
                case 'complaint_type':
                    $data['complaint_type'] = $contact->getComplaintType();
                    break;
                case 'complaint_subtype':
                    $data['complaint_subtype'] = $contact->getComplaintSubtype();
                    break;
                case 'last_bounce_at':
                    $data['last_bounce_at'] = DateTimeUtils::toDbDateTime($contact->getLastBounceAt());
                    break;
                case 'last_complaint_at':
                    $data['last_complaint_at'] = DateTimeUtils::toDbDateTime($contact->getLastComplaintAt());
                    break;
                case 'aws_feedback_id':
                    $data['aws_feedback_id'] = $contact->getAwsFeedbackId();
                    break;
                case 'bounce_count':
                    $data['bounce_count'] = $contact->getBounceCount();
                    break;
                case 'complaint_count':
                    $data['complaint_count'] = $contact->getComplaintCount();
                    break;
            }
        }
        if (count($data) > 0) {
            $data['updated_at'] = DateTimeUtils::toDbDateTime(new \DateTime());
            $this->connection->update($this->tablePrefix . 'contacts', $data, array('id' => $contact->getId()));
            return true;
        }
        return false;
    }

    /**
     * Update last email at
     * 
     * @param int $contactId
     * @return bool
     */
    public function updateLastEmailAt(int $contactId): bool
    {
        $this->connection->update($this->tablePrefix . 'contacts', [
            'last_email_at' => DateTimeUtils::toDbDateTime(),
        ], ['id' => $contactId]);
        return true;
    }

}