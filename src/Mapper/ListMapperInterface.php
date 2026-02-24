<?php

namespace SureLv\Emails\Mapper;

use SureLv\Emails\Provider\List\ListProviderInterface;

interface ListMapperInterface
{

    public function getProvider(string $name): ?ListProviderInterface;

    public function hasProvider(string $name): bool;

    /**
     * Get list of provider names
     * 
     * @return array<string>
     */
    public function getProviderNames(): array;
    
}