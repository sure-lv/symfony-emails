<?php

namespace SureLv\Emails\Enum;

enum ContactSuppressionReason: string
{
    case HARD_BOUNCE = 'hard_bounce';
    case TRANSIENT_BOUNCE = 'transient_bounce';
    case COMPLAINT = 'complaint';
    case MANUAL = 'manual';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}