<?php

namespace SureLv\Emails\Enum;

enum EmailEventType: string
{
    case DELIVERED = 'delivered';
    case OPEN = 'open';
    case CLICK = 'click';
    case BOUNCE = 'bounce';
    case COMPLAINT = 'complaint';
    case REJECT = 'reject';
    case SEND_FAIL = 'send_fail';
    case UNSUBSCRIBE = 'unsubscribe';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}