<?php
declare(strict_types=1);

namespace SureLv\Emails\Service;

use SureLv\Emails\Mapper\ListMapperInterface;
use SureLv\Emails\Provider\List\ListProviderInterface;

final class EmailsListService
{

    public function __construct(private ListMapperInterface $listMapper) {}

    /**
     * Get the list provider
     * 
     * @param string $name
     * @return \SureLv\Emails\Provider\List\ListProviderInterface
     */
    public function getProvider(string $name): ListProviderInterface
    {
        $provider = $this->listMapper->getProvider($name); /** @var \SureLv\Emails\Provider\List\ListProviderInterface $provider */
        if (!$provider instanceof ListProviderInterface) {
            throw new \InvalidArgumentException("No list provider registered for: {$name}");
        }
        return $provider;
    }

    /**
     * Get list of provider names
     * 
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return $this->listMapper->getProviderNames();
    }

}
