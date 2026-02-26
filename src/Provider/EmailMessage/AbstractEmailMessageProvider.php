<?php
declare(strict_types=1);

namespace SureLv\Emails\Provider\EmailMessage;

use SureLv\Emails\Dto\EmailMessageDto;
use SureLv\Emails\Dto\EmailMessageParamsDto;
use SureLv\Emails\Entity\Job;
use SureLv\Emails\Entity\EmailMessage;
use SureLv\Emails\Message\EnqueueTransactionalEmailMessage;
use SureLv\Emails\Model\EmailMessageModel;
use SureLv\Emails\Service\EmailsHelperService;
use SureLv\Emails\Service\EmailTrackingService;
use SureLv\Emails\Service\ModelService;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

abstract class AbstractEmailMessageProvider implements EmailMessageProviderInterface
{

    protected ModelService $modelService;
    protected EmailsHelperService $emailsHelperService;
    protected ContainerBagInterface $containerBag;
    protected Environment $twig;
    protected UrlGeneratorInterface $urlGenerator;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    protected ?EmailTrackingService $email_tracking_service = null;

    #[Required]
    public function setModuleDependencies(
        ModelService $modelService,
        EmailsHelperService $emailsHelperService,
        ContainerBagInterface $containerBag,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): void {
        $this->modelService = $modelService;
        $this->emailsHelperService = $emailsHelperService;
        $this->containerBag = $containerBag;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Initialize the provider
     * 
     * @param array<string, mixed> $config
     * @return void
     */
    final public function initProvider(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Set email tracking service
     * 
     * @param EmailTrackingService $emailTrackingService
     * @return void
     */
    final public function setEmailTrackingService(EmailTrackingService $emailTrackingService): void
    {
        $this->email_tracking_service = $emailTrackingService;
    }

    /**
     * Fulfill the message
     * 
     * @param EmailMessageDto $emailMessageDto
     * @param ?string &$error
     * @return bool
     */
    final public function fulfillEmailMessage(EmailMessageDto $emailMessageDto, ?string &$error = null): bool
    {
        try {
            // Validate prerequisites
            if (!$this->validateEmailMessageParams($emailMessageDto->getParamsDto(), $error)) {
                $error = 'Invalid email message params: ' . ($error ?? 'Unknown error');
                return false;
            }

            // Call child class implementation
            $this->buildMessage($emailMessageDto);

            // Set from email
            if (empty($emailMessageDto->getFromEmail())) {
                $emailMessageDto->setFromEmail($this->getFromEmail(true));
            }

            // Set context
            $this->setGlobalContext($emailMessageDto);

            // Update request locale
            if ($emailMessageDto->getGlobalContextValue('_locale')) {
                $this->urlGenerator->getContext()->setParameter('_locale', $emailMessageDto->getGlobalContextValue('_locale'));
            }

            // Set email message variables (unsubscribe link, etc.)
            $this->setEmailMessageVariables($emailMessageDto);

            // Set unsubscribe link to global context
            $emailMessageDto->addGlobalContextValue('unsubscribe_link', $emailMessageDto->getDataValue('unsubscribe_link'));

            // Set headers
            if ($emailMessageDto->getDataValue('x_entity_ref_id')) {
                $emailMessageDto->addHeader('X-Entity-Ref-ID', $emailMessageDto->getDataValue('x_entity_ref_id'));
            }
            if ($emailMessageDto->getDataValue('unsubscribe_link')) {
                $emailMessageDto->addHeader('List-Unsubscribe', '<' . $emailMessageDto->getDataValue('unsubscribe_link') . '>');
                $emailMessageDto->addHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            }

            // Set template metadata
            $emailMessage = $emailMessageDto->getEmailMessage();
            $emailMessage
                ->setSubject($emailMessageDto->getSubject())
                ->setFromEmail($emailMessageDto->getFromEmail() ?? $this->getFromEmail(true))
                ->setTemplateKey($emailMessageDto->getTemplateKey())
                ->setTemplateVersion($emailMessageDto->getTemplateVersion())
                ;
            if (count($emailMessageDto->getHeaders()) > 0) {
                $emailMessage->setHeaders($emailMessageDto->getHeaders());
            }
            if (!empty($emailMessageDto->getBodyHtmlPath())) {
                $emailMessage->setBodyHtml($this->renderTemplate($emailMessageDto->getBodyHtmlPath(), $emailMessageDto->getHtmlContext(true)));
            } elseif (!empty($emailMessageDto->getBodyHtml())) {
                $emailMessage->setBodyHtml($this->updateMessageBodyWithContext($emailMessageDto->getBodyHtml(), $emailMessageDto->getHtmlContext(true)));
            }
            if (!empty($emailMessageDto->getBodyPlainPath())) {
                $emailMessage->setBodyPlain($this->renderTemplate($emailMessageDto->getBodyPlainPath(), $emailMessageDto->getPlainContext(true)));
            } elseif (!empty($emailMessageDto->getBodyPlain())) {
                $emailMessage->setBodyPlain($this->updateMessageBodyWithContext($emailMessageDto->getBodyPlain(), $emailMessageDto->getPlainContext(true)));
            }

            // Add tracking to html
            if ($this->email_tracking_service && $emailMessage->getBodyHtml()) {
                $emailMessage->setBodyHtml($this->email_tracking_service->addTracking($emailMessage->getBodyHtml(), $emailMessage->getId()));
            }

            // Generate checksums after content is set
            $this->generateChecksums($emailMessageDto->getEmailMessage());

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Validate and save the message
     * 
     * @param EmailMessage $emailMessage
     * @param ?string &$error
     * @return bool
     */
    public function isValidFulfilledMessage(EmailMessage $emailMessage, ?string &$error = null): bool
    {
        $errors = [];

        if (empty($emailMessage->getSubject())) {
            $errors[] = 'Subject is required';
        }

        if (empty($emailMessage->getFromEmail())) {
            $errors[] = 'From email is required';
        }

        if (empty($emailMessage->getToEmail())) {
            $errors[] = 'To email is required';
        }

        if (empty($emailMessage->getBodyHtml())) {
            $errors[] = 'HTML body is required';
        }

        if (empty($emailMessage->getTemplateKey())) {
            $errors[] = 'Template key is required';
        }

        if ($emailMessage->getTemplateVersion() === null) {
            $errors[] = 'Template version is required';
        }

        if (!empty($errors)) {
            $error = 'Message fulfillment incomplete: ' . implode(', ', $errors);
            return false;
        }

        return true;
    }

    /**
     * Save the message
     * 
     * @param EmailMessage $emailMessage
     * @return void
     */
    public function saveMessage(EmailMessage $emailMessage): void
    {
        $emailMessageModel = $this->modelService->getModel(EmailMessageModel::class); /** @var \App\Domain\Emails\Model\EmailMessageModel $emailMessageModel */
        $emailMessageModel->update($emailMessage);
    }

    /**
     * Post-fulfill email message
     * 
     * @param EmailMessageDto $emailMessageDto
     * @return void
     */
    public function postEmailMessageFulfill(EmailMessageDto $emailMessageDto): void
    {
    }

    /**
     * Get the next transactional email message for queue
     * 
     * @param Job $job
     * @param EmailMessageParamsDto $paramsDto
     * @return ?EnqueueTransactionalEmailMessage
     */
    public function getNextTransactionalEmailMessageForQueue(Job $job, EmailMessageParamsDto $paramsDto): ?EnqueueTransactionalEmailMessage
    {
        return null;
    }


    /**
     * 
     * PROTECTED METHODS
     * 
     */


    /**
     * Validate the email message params
     * 
     * @param EmailMessageParamsDto $paramsDto
     * @param ?string &$error
     * @return bool
     */
    abstract protected function validateEmailMessageParams(EmailMessageParamsDto $paramsDto, ?string &$error = null): bool;
    
    /**
     * Build the email message
     * 
     * @param EmailMessageDto $emailMessageDto
     * @return void
     */
    abstract protected function buildMessage(EmailMessageDto $emailMessageDto): void;

    /**
     * Generate checksums for rendered content
     * 
     * @param EmailMessage $emailMessage
     * @return void
     */
    protected function generateChecksums(EmailMessage $emailMessage): void
    {
        $htmlBody = $emailMessage->getBodyHtml();
        $plainBody = $emailMessage->getBodyPlain();

        if (!empty($htmlBody)) {
            $emailMessage->setRenderChecksumHtml(
                hash('sha256', $htmlBody)
            );
        }

        if (!empty($plainBody)) {
            $emailMessage->setRenderChecksumText(
                hash('sha256', $plainBody)
            );
        }
    }

    /**
     * Set global context
     * 
     * @param EmailMessageDto $emailMessageDto
     * @return void
     */
    protected function setGlobalContext(EmailMessageDto $emailMessageDto): void
    {
    }

    /**
     * Helper to render Twig templates with error handling
     * 
     * @param string $templatePath
     * @param array<string, mixed> $context
     * @return string
     */
    protected function renderTemplate(string $templatePath, array $context = []): string
    {
        try {
            return $this->twig->render($templatePath, $context);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to render template '{$templatePath}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update message body with context
     * 
     * @param string $body
     * @param array<string, mixed> $context
     * @return string
     */
    protected function updateMessageBodyWithContext(string $body, array $context = []): string
    {
        foreach ($context as $key => $value) {
            $body = preg_replace('/\{\{\s*' . $key . '\s*\}\}/', (string)$value, $body);
            if (!$body) {
                break;
            }
        }
        
        return $body ?? '';
    }

    /**
     * Get the from email
     * 
     * @param bool $formated
     * @return string
     */
    protected function getFromEmail(bool $formated = false): string
    {
        if ($formated) {
            $email = $this->emailsHelperService->getEmailsConfig()->fromEmailFormated;
        } else {
            $email = $this->emailsHelperService->getEmailsConfig()->fromEmail;
        }
        if (empty($email)) {
            throw new \RuntimeException('From email is not configured');
        }
        return $email;
    }

    /**
     * Set email message variables
     * 
     * @param EmailMessageDto $emailMessageDto
     * @return void
     */
    protected function setEmailMessageVariables(EmailMessageDto $emailMessageDto): void
    {
        $emailMessageDto->addDataValue('x_entity_ref_id', null); // 'X-Entity-Ref-ID' => $this->campaignName . '|' . $contact->getId(),
        if ($emailMessageDto->getWithUnsubscribe()) {
            $emailMessageDto->addDataValue('unsubscribe_link', $this->getUnsubscribeLink($emailMessageDto));
        }
    }


    /**
     * 
     * PRIVATE METHODS
     * 
     */


    /**
     * Get unsubscribe link
     * 
     * @param EmailMessageDto $emailMessageDto
     * @return ?string
     */
    private function getUnsubscribeLink(EmailMessageDto $emailMessageDto): ?string
    {
        if (!$emailMessageDto->getMember()) {
            return null;
        }
        
        $member = $emailMessageDto->getMember();
        $systemParams = $emailMessageDto->getSystemParams();
        $listParams = $systemParams ? $systemParams->getListById($member->getListId()) : null;
        
        $params = [
            'mi' => $emailMessageDto->getEmailMessage()->getId(),
            'i' => $member->getId(),
            's' => $listParams ? $listParams->getSubType() : null,
        ];

        [$payload, $signature] = $this->emailsHelperService->getPayloadTokenParts($params);
        
        return $this->urlGenerator->generate('sure_lv_emails_unsubscribe', ['memberId' => $member->getId(), 'messageId' => $emailMessageDto->getEmailMessage()->getId(), 'payload' => $payload, 'signature' => $signature], UrlGeneratorInterface::ABSOLUTE_URL);
    }

}
