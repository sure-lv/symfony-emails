<?php

namespace SureLv\Emails\Enum;

enum ListMemberStatus: string
{
    case SUBSCRIBED = 'subscribed';
    case UNSUBSCRIBED = 'unsubscribed';
    case CLEANED = 'cleaned';
    case DISABLED = 'disabled';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}