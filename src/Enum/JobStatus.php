<?php

namespace SureLv\Emails\Enum;

enum JobStatus: string
{
    
    case DRAFT = 'draft';
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed'; // failed to process
    case SKIPPED = 'skipped'; // skipped due to configuration or other reason
    case CANCELLED = 'cancelled'; // cancelled by user

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
    
}