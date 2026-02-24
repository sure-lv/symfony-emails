<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use SureLv\Emails\Dto\JobSystemParamsDto;
use SureLv\Emails\Entity\Contact;
use SureLv\Emails\Entity\EmailsList;
use SureLv\Emails\Exception\RateLimitExceededException;
use SureLv\Emails\Message\EnqueueListEmailMessage;
use SureLv\Emails\Message\EnqueueTransactionalEmailMessage;
use SureLv\Emails\Model\ContactModel;
use SureLv\Emails\Model\EmailsListModel;
use Symfony\Component\Messenger\MessageBusInterface;

class RecipeMessenger
{
    
    public function __construct(
        private MessageBusInterface $bus,
        private ModelService $modelService,
        private RegistryService $registryService,
        private EmailsLogger $emailsLogger,
        private RateLimiterService $rateLimiter
    ) {}

    /**
     * Dispatch list email
     * 
     * @param string $recipeName
     * @param \SureLv\Emails\Entity\EmailsList|null $list
     * @param string|null $listName
     * @param string|null $listSubType
     * @param array<string, mixed> $params
     * @param \DateTimeImmutable|null $runAt
     * @param int|null $stepOrder
     * @param int|null $priority
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function dispatchList(
        string $recipeName,
        ?EmailsList $list,
        ?string $listName,
        ?string $listSubType,
        array $params = [],
        ?\DateTimeImmutable $runAt = null,
        ?int $stepOrder = null,
        ?int $priority = null
    ): void {
        
        // Get recipe
        if (!$this->registryService->hasRecipe($recipeName)) {
            $this->emailsLogger->logError('Recipe not found in registry', [
                'recipe' => $recipeName,
            ]);
            throw new \InvalidArgumentException('Recipe not found in registry');
        }
        $recipe = $this->registryService->getRecipe($recipeName);

        // Get list
        if (is_null($list) && is_string($listName) && !empty($listName)) {
            $emailsListModel = $this->modelService->getModel(EmailsListModel::class); /** @var \SureLv\Emails\Model\EmailsListModel $emailsListModel */
            $list = $emailsListModel->getListByName($listName);
            if (is_null($list)) {
                $this->emailsLogger->logError('List not found', [
                    'list_name' => $listName,
                ]);
                throw new \InvalidArgumentException('List not found');
            }
        }
        $listId = $list ? $list->getId() : 0;

        // Get system params
        $systemParams = JobSystemParamsDto::createFromArray($params['__'] ?? []);
        unset($params['__']);

        // Get contact (if email is provided)
        $email = $systemParams->getContactEmail();
        if (!empty($email)) {

            try {
                $contact = Contact::createFromEmail($email);
                $this->emailsLogger->logDebug('Contact created from email', [
                    'email' => $email,
                ]);
            } catch (\Exception $e) {
                $this->emailsLogger->logError('Failed to create contact from email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
    
            $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
            if (!$contactModel->add($contact)) {
                $this->emailsLogger->logError('Failed to add contact to database', [
                    'email' => $contact->getEmail(),
                ]);
                throw new \Exception('Failed to add contact to database');
            }

            $systemParams
                ->setContactId($contact->getId())
                ->setContactEmail($email)
                ;

        }

        // Merge system params back to params
        $params = array_merge(['__' => 	$systemParams->toArray()], $params);

        try {

            $message = new EnqueueListEmailMessage(
                recipeName: $recipe->getName(),
                listId: $listId,
                listSubType: $listSubType,
                runAt: $runAt ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                params: $params,
                stepOrder: $stepOrder ?? 0,
                priority: $priority ?? 0
            );

            $this->emailsLogger->logInfo('Dispatching message to bus', [
                'recipe' => $recipe->getName(),
                'list_id' => $listId,
            ]);

            $this->bus->dispatch($message);

            $this->emailsLogger->logInfo('Message dispatched successfully', [
                'recipe' => $recipe->getName(),
                'list_id' => $listId,
            ]);

        } catch (\Exception $e) {
            $this->emailsLogger->logError('Failed to dispatch message', [
                'recipe' => $recipe->getName(),
                'list_id' => $listId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch list email by scope
     * 
     * @param string $recipeName
     * @param \SureLv\Emails\Entity\EmailsList|null $list
     * @param string|null $listName
     * @param string|null $listSubType
     * @param string $email
     * @param string $scopeType
     * @param int $scopeId
     * @param array<string, mixed> $params
     * @param \DateTimeImmutable|null $runAt
     * @param int|null $stepOrder
     * @param int|null $priority
     * @return void
     */
    public function dispatchListByScope(
        string $recipeName,
        ?EmailsList $list,
        ?string $listName,
        ?string $listSubType,
        string $email,
        string $scopeType,
        int $scopeId,
        array $params = [],
        ?\DateTimeImmutable $runAt = null,
        ?int $stepOrder = null,
        ?int $priority = null
    ): void {

        // Get system params
        $systemParams = JobSystemParamsDto::createFromArray($params['__'] ?? []);

        // Get contact param
        $systemParams->setContactEmail($email);

        // Get scope param
        $systemParams
            ->setScopeType($scopeType)
            ->setScopeId($scopeId)
            ;

        // Merge system params back to params
        $params['__'] = $systemParams->toArray();

        // Dispatch list email
        $this->dispatchList($recipeName, $list, $listName, $listSubType, $params, $runAt, $stepOrder, $priority);
    }

    /**
     * Dispatch transactional email
     * 
     * @param string $recipeName
     * @param \SureLv\Emails\Entity\Contact|null $contact
     * @param string|null $email
     * @param array<string, mixed> $params
     * @param \DateTimeImmutable|null $runAt
     * @param int|null $stepOrder
     * @param int|null $priority
     * @return void
     * @throws \InvalidArgumentException
     */
    public function dispatchTransactional(
        string $recipeName,
        ?Contact $contact,
        ?string $email,
        array $params = [],
        ?\DateTimeImmutable $runAt = null,
        ?int $stepOrder = null,
        ?int $priority = null
    ): void {
        $this->emailsLogger->logInfo('dispatchTransactional called', [
            'recipe' => $recipeName,
            'email' => $email,
            'has_contact' => !is_null($contact),
            'params' => $params,
            'run_at' => $runAt?->format('Y-m-d H:i:s'),
            'step_order' => $stepOrder,
            'priority' => $priority,
        ]);

        // Get recipe
        if (!$this->registryService->hasRecipe($recipeName)) {
            $this->emailsLogger->logError('Recipe not found in registry', [
                'recipe' => $recipeName,
            ]);
            throw new \InvalidArgumentException('Recipe not found in registry');
        }
        $recipe = $this->registryService->getRecipe($recipeName);

        if (is_null($contact) && is_null($email)) {
            $this->emailsLogger->logError('Either contact or email must be provided');
            throw new \InvalidArgumentException('Either contact or email must be provided');
        }

        if (is_null($contact)) {
            try {
                $contact = Contact::createFromEmail($email);
                $this->emailsLogger->logDebug('Contact created from email', [
                    'email' => $email,
                ]);
            } catch (\Exception $e) {
                $this->emailsLogger->logError('Failed to create contact from email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        if ($contact->getId() <= 0) {
            $contactModel = $this->modelService->getModel(ContactModel::class); /** @var \SureLv\Emails\Model\ContactModel $contactModel */
            if (!$contactModel->add($contact)) {
                $this->emailsLogger->logError('Failed to add contact to database', [
                    'email' => $contact->getEmail(),
                ]);
                throw new \Exception('Failed to add contact to database');
            }
        }

        // Check if contact is suppressed
        if ($contact->isSuppressed()) {
            $this->emailsLogger->logWarning('Contact is suppressed, skipping email', [
                'email' => $contact->getEmail(),
                'suppressed_until' => $contact->getSuppressedUntil()?->format('Y-m-d H:i:s'),
                'reason' => $contact->getSuppressionReason()?->value,
            ]);
            throw new \Exception('Contact is suppressed');
        }

        try {

            // Check email rate limit
            $this->rateLimiter->checkEmailLimit($contact->getEmail());

            $message = new EnqueueTransactionalEmailMessage(
                recipeName: $recipe->getName(),
                contactId: $contact->getId(),
                runAt: $runAt ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                params: $params,
                stepOrder: $stepOrder,
                priority: $priority ?? 0
            );

            $this->emailsLogger->logInfo('Dispatching message to bus', [
                'recipe' => $recipe->getName(),
                'contact_email' => $contact->getEmail(),
            ]);

            $this->bus->dispatch($message);

            $this->emailsLogger->logInfo('Message dispatched successfully', [
                'recipe' => $recipe->getName(),
                'contact_email' => $contact->getEmail(),
                'contact_id' => $contact->getId(),
            ]);

        } catch (RateLimitExceededException $e) {
            
            $this->emailsLogger->logError('Rate limit exceeded', [
                'recipe' => $recipe->getName(),
                'email' => $contact->getEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

        } catch (\Exception $e) {

            $this->emailsLogger->logError('Failed to dispatch message', [
                'recipe' => $recipe->getName(),
                'email' => $contact->getEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // throw $e;
            
        }
    }

}
