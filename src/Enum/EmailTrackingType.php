<?php

namespace SureLv\Emails\Enum;

enum EmailTrackingType: string
{
    case CLICK = 'click';
    case OPEN = 'open';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }

}