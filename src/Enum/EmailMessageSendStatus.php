<?php

namespace SureLv\Emails\Enum;

enum EmailMessageSendStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';
    case BOUNCED = 'bounced';
    case COMPLAINED = 'complained';
    case DELIVERED = 'delivered';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}