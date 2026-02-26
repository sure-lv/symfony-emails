<?php

namespace SureLv\Emails\Service;

use SureLv\Emails\Mapper\EmailMessageMapperInterface;
use SureLv\Emails\Provider\EmailMessage\EmailMessageProviderInterface;

class EmailMessageService
{

    /**
     * @var array<string, ?EmailMessageProviderInterface>
     */
    private array $loaded_providers = [];

    public function __construct(private ?EmailMessageMapperInterface $emailMessageMapper, private EmailTrackingService $emailTrackingService) {}

    /**
     * Get provider instance
     * 
     * @param string $name
     * @return EmailMessageProviderInterface
     */
    public function getProvider(string $name): EmailMessageProviderInterface
    {
        if (isset($this->loaded_providers[$name])) {
            return $this->loaded_providers[$name];
        }

        if (!$this->emailMessageMapper) {
            throw new \Exception("No email message mapper registered");
        }

        $provider = $this->emailMessageMapper->getProvider($name);
        if (!$provider instanceof EmailMessageProviderInterface) {
            throw new \InvalidArgumentException("No email message provider registered for: {$name}");
        }

        $provider->initProvider([]);
        $provider->setEmailTrackingService($this->emailTrackingService);
        $this->loaded_providers[$name] = $provider;

        return $provider;
    }

}