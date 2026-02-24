<?php

namespace SureLv\Emails\Mapper;

use SureLv\Emails\Provider\EmailMessage\EmailMessageProviderInterface;

interface EmailMessageMapperInterface
{

    public function getProvider(string $name): ?EmailMessageProviderInterface;

    public function hasProvider(string $name): bool;

    /**
     * Get list of provider names
     * 
     * @return array<string>
     */
    public function getProviderNames(): array;
    
}