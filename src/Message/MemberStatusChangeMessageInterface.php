<?php

namespace SureLv\Emails\Message;

interface MemberStatusChangeMessageInterface
{
 
    /**
     * Constructor
     * 
     * @param string $toStatus
     * @param ?string $fromStatus
     * @param ?string $subType
     * @param string $scopeType
     * @param int $scopeId
     * @param array<string, mixed> $params
     * @param array<string, mixed> $context
     */
    public function __construct(string $toStatus, ?string $fromStatus, ?string $subType, string $scopeType, int $scopeId, array $params = [], array $context = []);

}