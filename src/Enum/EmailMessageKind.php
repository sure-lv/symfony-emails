<?php

namespace SureLv\Emails\Enum;

enum EmailMessageKind: string
{
    case TRANSACTIONAL = 'transactional';
    case LIST = 'list';

    public static function tryFromString(?string $type): ?self
    {
        return $type ? self::tryFrom(strtolower($type)) : null;
    }
}