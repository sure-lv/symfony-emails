<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Enum\ContactSuppressionReason;
use SureLv\Emails\Enum\EmailEventType;
use SureLv\Emails\Enum\EmailMessageSendStatus;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailMessageModel;
use SureLv\Emails\Util\DateTimeUtils;
use SureLv\Emails\Util\EmailUtils;

class EmailStatusUpdater
{

    /**
     * @var \SureLv\Emails\Model\ContactModel|null
     */
    private ?ContactModel $contact_model = null;

    /**
     * Constructor
     * 
     * @param \SureLv\Emails\Service\ModelService $modelService
     */
    public function __construct(private ModelService $modelService, private EmailEventService $emailEventService) {}

    /**
     * Handle bounce
     * 
     * @param array<string, mixed> $bounceData
     * @param array<string, mixed> $mailData
     * @return void
     */
    public function handleBounce(array $bounceData, array $mailData): void
    {
        $bounceType = $bounceData['bounceType'] ?? null;
        $bounceSubType = $bounceData['bounceSubType'] ?? null;
        $timestamp = isset($bounceData['timestamp']) ? new \DateTime($bounceData['timestamp']) : new \DateTime();
        $feedbackId = $bounceData['feedbackId'] ?? null;
        $bouncedRecipients = $bounceData['bouncedRecipients'] ?? [];

        // Handle all bounced recipients
        foreach ($bouncedRecipients as $recipient) {
            $emailAddress = $recipient['emailAddress'] ?? null;
            if (!$emailAddress) {
                continue;
            }

            $contact = $this->getContactByEmail($emailAddress);
            if (!$contact) {
                continue;
            }

            // Extract diagnostic code if available
            $diagnosticCode = $recipient['diagnosticCode'] ?? null;

            // Update bounce fields
            $contact
                ->setBounceType($bounceType)
                ->setBounceSubtype($bounceSubType)
                ->setBounceDiagnosticCode($diagnosticCode)
                ->setLastBounceAt($timestamp)
                ->setBounceCount($contact->getBounceCount() + 1)
            ;

            if ($feedbackId) {
                $contact->setAwsFeedbackId($feedbackId);
            }

            // Handle permanent bounces - suppress permanently
            if ($bounceType === 'Permanent') {
                $contact
                    ->setSuppressionReason(ContactSuppressionReason::HARD_BOUNCE)
                    ->setSuppressedUntil(DateTimeUtils::getMaxDatetime())
                    ;
                $fieldsToUpdate = [
                    'bounce_type',
                    'bounce_subtype',
                    'bounce_diagnostic_code',
                    'last_bounce_at',
                    'bounce_count',
                    'suppression_reason',
                    'suppressed_until',
                ];
            } else {
                // Transient/Undetermined bounces - just track, don't suppress permanently
                $fieldsToUpdate = [
                    'bounce_type',
                    'bounce_subtype',
                    'bounce_diagnostic_code',
                    'last_bounce_at',
                    'bounce_count',
                ];
                if ($contact->getBounceCount() >= 4) {
                    $contact
                        ->setSuppressionReason(ContactSuppressionReason::HARD_BOUNCE)
                        ->setSuppressedUntil(DateTimeUtils::getMaxDatetime())
                        ;
                    $fieldsToUpdate[] = 'suppression_reason';
                    $fieldsToUpdate[] = 'suppressed_until';
                } elseif ($contact->getBounceCount() >= 3) {
                    $contact
                        ->setSuppressionReason(ContactSuppressionReason::TRANSIENT_BOUNCE)
                        ->setSuppressedUntil((new \DateTime())->modify('+90 days'))
                        ;
                    $fieldsToUpdate[] = 'suppression_reason';
                    $fieldsToUpdate[] = 'suppressed_until';
                } elseif ($contact->getBounceCount() >= 2) {
                    $contact
                        ->setSuppressionReason(ContactSuppressionReason::TRANSIENT_BOUNCE)
                        ->setSuppressedUntil((new \DateTime())->modify('+30 days'))
                        ;
                    $fieldsToUpdate[] = 'suppression_reason';
                    $fieldsToUpdate[] = 'suppressed_until';
                }
            }

            if ($feedbackId) {
                $fieldsToUpdate[] = 'aws_feedback_id';
            }

            $this->getContactModel()->updateContactRecord($contact, $fieldsToUpdate);
        }

        // Update message status if we have message ID
        if (isset($mailData['messageId'])) {
            $this->updateEmailMessageStatus($mailData['messageId'], EmailMessageSendStatus::BOUNCED, $bounceData['bouncedRecipients'][0]['diagnosticCode'] ?? 'Email bounced');
        }
    }

    /**
     * Handle complaint
     * 
     * @param array<string, mixed> $complaintData
     * @param array<string, mixed> $mailData
     * @return void
     */
    public function handleComplaint(array $complaintData, array $mailData): void
    {
        $complaintFeedbackType = $complaintData['complaintFeedbackType'] ?? null;
        $complaintSubType = $complaintData['complaintSubType'] ?? null;
        $timestamp = isset($complaintData['timestamp']) ? new \DateTime($complaintData['timestamp']) : new \DateTime();
        $feedbackId = $complaintData['feedbackId'] ?? null;
        $complainedRecipients = $complaintData['complainedRecipients'] ?? [];

        // Handle all complained recipients
        foreach ($complainedRecipients as $recipient) {
            $emailAddress = $recipient['emailAddress'] ?? null;
            if (!$emailAddress) {
                continue;
            }

            $contact = $this->getContactByEmail($emailAddress);
            if (!$contact) {
                continue;
            }

            // Update complaint fields
            $contact
                ->setComplaintType($complaintFeedbackType)
                ->setComplaintSubtype($complaintSubType)
                ->setLastComplaintAt($timestamp)
                ->setComplaintCount($contact->getComplaintCount() + 1)
            ;

            if ($feedbackId) {
                $contact->setAwsFeedbackId($feedbackId);
            }

            // Complaints result in permanent suppression
            $contact
                ->setSuppressionReason(ContactSuppressionReason::COMPLAINT)
                ->setSuppressedUntil(DateTimeUtils::getMaxDatetime()) // null means permanent suppression
                ;

            $fieldsToUpdate = [
                'complaint_type',
                'complaint_subtype',
                'last_complaint_at',
                'complaint_count',
                'suppression_reason',
                'suppressed_until',
            ];

            if ($feedbackId) {
                $fieldsToUpdate[] = 'aws_feedback_id';
            }

            $this->getContactModel()->updateContactRecord($contact, $fieldsToUpdate);
        }

        // Update message status if we have message ID
        if (isset($mailData['messageId'])) {
            $this->updateEmailMessageStatus($mailData['messageId'], EmailMessageSendStatus::COMPLAINED, 'Marked as spam by recipient');
        }
    }

    /**
     * Handle delivery
     * 
     * @param array<string, mixed> $mailData
     * @return void
     */
    public function handleDelivery(array $mailData): void
    {
        // Update message status if we have message ID
        if (isset($mailData['messageId'])) {
            $this->updateEmailMessageStatus($mailData['messageId'], EmailMessageSendStatus::DELIVERED);
        }
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Get contact by email address
     * 
     * @param string $emailAddress
     * @return \SureLv\Emails\Entity\Contact|null
     */
    private function getContactByEmail(string $emailAddress): ?Contact
    {
        return $this->getContactModel()->getByEmail(EmailUtils::extractEmailAddress($emailAddress));
    }

    /**
     * Get contact model
     * 
     * @return \SureLv\Emails\Model\ContactModel
     */
    private function getContactModel(): ContactModel
    {
        if (is_null($this->contact_model)) {
            $model = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $model */
            $this->contact_model = $model;
        }
        return $this->contact_model;
    }

    /**
     * Update email message status
     * 
     * @param string $senderMessageId
     * @param EmailMessageSendStatus $sendStatus
     * @param string|null $failReason
     * @return void
     */
    private function updateEmailMessageStatus(string $senderMessageId, EmailMessageSendStatus $sendStatus, ?string $failReason = null): void
    {
        $emailMessageModel = $this->modelService->getModel(EmailMessageModel::class); /** @var \SureLv\Emails\Model\EmailMessageModel $emailMessageModel */
        
        $emailMessage = $emailMessageModel->getBySenderMessageId($senderMessageId);
        if (!$emailMessage) {
            return;
        }

        $emailMessageModel->updateStatusByMessageId($emailMessage->getId(), $sendStatus, $failReason);

        $eventType = null;
        if ($sendStatus == EmailMessageSendStatus::DELIVERED) {
            $eventType = EmailEventType::DELIVERED;
        } elseif ($sendStatus == EmailMessageSendStatus::BOUNCED) {
            $eventType = EmailEventType::BOUNCE;
        } elseif ($sendStatus == EmailMessageSendStatus::COMPLAINED) {
            $eventType = EmailEventType::COMPLAINT;
        } elseif ($sendStatus == EmailMessageSendStatus::FAILED) {
            $eventType = EmailEventType::SEND_FAIL;
        }
        if ($eventType) {
            $this->emailEventService->register($emailMessage->getId(), $eventType);
        }
    }
    
}
