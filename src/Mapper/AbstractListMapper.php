<?php

namespace SureLv\Emails\Mapper;

use Psr\Container\ContainerInterface;
use SureLv\Emails\Provider\List\ListProviderInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractListMapper implements ListMapperInterface, ServiceSubscriberInterface
{

    protected static array $providers = [];

    /**
     * Get list of available providers
     * 
     * @return array<string>
     */
    public static function getSubscribedServices(): array
    {
        return array_values(static::$providers);
    }

    /**
     * Constructor
     * 
     * @param \Psr\Container\ContainerInterface $locator
     */
    public function __construct(private ContainerInterface $locator)
    {
    }

    /**
     * Get list provider
     * 
     * @param string $name
     * @return \SureLv\Emails\Provider\List\ListProviderInterface
     * @throws \InvalidArgumentException if provider is not registered
     */
    public function getProvider(string $name): ?ListProviderInterface
    {
        $providerClass = static::$providers[$name] ?? null;
        if ($providerClass === null || !$this->locator->has($providerClass)) {
            throw new \InvalidArgumentException("No list provider registered for: {$name}");
        }

        $provider = $this->locator->get($providerClass); /** @var \SureLv\Emails\Provider\List\ListProviderInterface $provider */
        return $provider;
    }

    /**
     * Check if a provider is registered
     * 
     * @param string $name
     * @return bool
     */
    public function hasProvider(string $name): bool
    {
        return isset(static::$providers[$name]);
    }

    /**
     * Get list of provider names
     * 
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return array_keys(static::$providers);
    }

}