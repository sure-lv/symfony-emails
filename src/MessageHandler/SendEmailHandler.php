<?php

namespace SureLv\Emails\MessageHandler;

use SureLv\Emails\Exception\Sender\SenderSendExceptionInterface;
use SureLv\Emails\Exception\Sender\SenderSendFailedException;
use SureLv\Emails\Exception\Sender\SenderSendInvalidArgumentException;
use SureLv\Emails\Exception\Sender\SenderSendWarningException;
use SureLv\Emails\Message\SendEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailMessageModel;
use SureLv\Emails\Service\EmailSenderService;
use SureLv\Emails\Service\EmailsLogger;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendEmailHandler
{

    public function __construct(
        private ModelService $modelService,
        private EmailsLogger $emailsLogger,
        private EmailSenderService $emailSenderService
    ) { }

    public function __invoke(SendEmailMessage $message): void
    {   
        $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
        $emailMessageModel = $this->modelService->getModel(EmailMessageModel::class); /** @var \SureLv\Emails\Model\EmailMessageModel $emailMessageModel */
        
        // Load message
        $emailMessageId = $message->getEmailMessageId();
        $emailMessage = $emailMessageModel->getById($emailMessageId);
        if (!$emailMessage) {
            $this->emailsLogger->logError('Email message not found', [
                'email_message_id' => $emailMessageId,
            ]);
            return;
        }

        $transport = $this->emailSenderService->getTransport();

        $exception = null;

        try {
            // Send email
            $transport->send($emailMessage);

            // Get message ID
            $senderMessageId = $transport->getMessageId();
            
            // Update message as sent
            $emailMessageModel->updateAsSent($emailMessage->getId(), $senderMessageId ?? '');
            
            $this->emailsLogger->logInfo('Email sent successfully', [
                'email_message_id' => $emailMessage->getId(),
                'sender_message_id' => $senderMessageId,
                'to_email' => $emailMessage->getToEmail(),
            ]);

            // Update last email at
            $contactModel->updateLastEmailAt($emailMessage->getContactId());

        } catch (SenderSendWarningException $e) {
            
            $this->emailsLogger->logWarning($e->getMessage(), [
                'email_message_id' => $emailMessage->getId(),
            ]);
            $exception = $e;
            
        } catch (SenderSendFailedException $e) {
            
            $this->emailsLogger->logCritical($e->getMessage(), [
                'email_message_id' => $emailMessage->getId(),
            ]);
            $exception = $e;
            $emailMessageModel->updateAsFailed($emailMessage->getId(), $e->getMessage());
            
        } catch (SenderSendInvalidArgumentException $e) {
            
            $this->emailsLogger->logError('Invalid message data', [
                'email_message_id' => $emailMessage->getId(),
                'error' => $e->getMessage(),
            ]);
            $exception = $e;
            $emailMessageModel->updateAsFailed($emailMessage->getId(), 'Validation error: ' . $e->getMessage());

        } catch (\Throwable $e) {

            $this->emailsLogger->logError('Unexpected error sending email', [
                'email_message_id' => $emailMessage->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $exception = $e;

        }

        if ($exception && $exception instanceof SenderSendExceptionInterface && $exception->shouldRetry()) {
            throw $exception;
        }

    }

}
